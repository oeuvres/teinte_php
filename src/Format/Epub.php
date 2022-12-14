<?php

declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Format;

use DOMDocument, DOMNodeList, DOMXpath;
use Oeuvres\Kit\{Check, I18n, Log, Xsl};


/**
 * Extract texts from epub files
 */
class Epub extends Zip
{
    /** Opf content as a string */
    private ?string $opf_xml;
    /** path of the opf file */
    private ?string $opf_path;
    /** opf directory to solve relative path */
    private ?string $opf_dir;
    /** toc directory to solve relative path */
    private ?string $ncx_dir;
    /** dom version of the opf */
    private ?DOMDocument $opf_dom;
    /** xpath version of the opf */
    private ?DOMXpath $opf_xpath;
    /**  html to concat */
    private ?string $html;
    /** manifest of resources, map id => path */
    private $manifest = [];
    /** spine in order */
    private $spine = [];
    /** Config for tidy html, used for inserted fragments: http://tidy.sourceforge.net/docs/quickref.html */
    public static $tidyconf = array(
        // will strip all unknown tags like <svg> or <section>
        'clean' => true, // MSO mess
        'doctype' => "omit",
        // 'force-output' => true, // let tidy complain
        // 'indent' => true, // xsl done
        'input-encoding' => "utf8", // ?? OK ?
        'newline' => "LF",
        'numeric-entities' => true,
        // 'new-blocklevel-tags' => 'section',
        // 'char-encoding' => "utf8",
        'output-encoding' => "utf8", // ?? OK ?
        'output-xhtml' => true,
        // 'output-xml' => true, // show-body-only will bug with <svg> => <html>
        // 'preserve-entities' => false, // unknown
        // 'quote-nbsp' => false,
        'wrap' => false,
        'show-body-only' => true,
    );

    /**
     * Load and check
     */
    public function load(string $file): bool
    {
        $this->chops = 0;
        if (!parent::load($file)) {
            return false;
        }
        if (null === ($cont = $this->get('META-INF/container.xml'))) {
            return false;
        }
        if (!preg_match('@full-path="([^"]+)"@', $cont, $matches)) {
            Log::warning(I18n::_('Epub.opf400', $file));
            return false;
        }
        $this->opf_path = urldecode($matches[1]);
        if (null === ($this->opf_xml = $this->get($this->opf_path))) {
            return false;
        }
        // set dir for path resolution in opf
        $this->opf_dir = dirname($this->opf_path);
        if ($this->opf_dir == ".") $this->opf_dir = "";
        else $this->opf_dir .= "/"; // ensure ending slash
        $this->opf_dom = Xsl::loadXml($this->opf_xml);
        // validate minimum required elements
        $ok = true;
        foreach (['metadata', 'manifest', 'spine'] as $el) {
            $nl = $this->opf_dom->getElementsByTagName('manifest');
            if (!$nl) {
                Log::warning(I18n::_('Epub.opfel404', $this->file, $this->opf_pat, $el));
                $ok = false;
            }
        }
        if (!$ok) return false;
        $this->opf_xpath = new DOMXpath($this->opf_dom);
        $this->opf_xpath->registerNamespace("opf", "http://www.idpf.org/2007/opf");
        return true;
    }

    /**
     * Build an HTML File
     */
    public function html()
    {
        // charger le container opf, contient les métadonnées et autres liens

        $this->html = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:epub="http://www.idpf.org/2007/ops"
  xmlns:opf="http://www.idpf.org/2007/opf"
>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
';
        $metadata = $this->opf_dom->getElementsByTagName('metadata')->item(0);
        // TODO, better work on meta
        $this->html .= $this->opf_dom->saveXML($metadata);
        $this->html .= '
  </head>
  <body>
';
        // parse toc and spline
        $this->read();
        $this->html .= '
  </body>
</html>
';
        return $this->html;
    }

    /**
     * parse spline or toc to get pages in order
     */
    private function read(): bool
    {
        $ncx_xml = $this->ncx_xml();
        if (!$ncx_xml) return false;
        // $this->html .= $ncx_xml;
        $ncx_dom = Xsl::loadXml($ncx_xml);
        // read the toc needs spine that needs manifest
        $this->manifest();
        $this->spine();
        // get an html from the toc
        $sections = Xsl::transformToXml(dirname(__DIR__).'/ncx_html.xsl', $ncx_dom);
        $sections = preg_replace(
            ["/<body[^>]*>/", "/<\/body>/"],
            ["", ""],
            $sections
        );
        $sections = preg_replace_callback(
            '/<content src="([^"]+)"( to="([^"]+)")?\/>/',
            function ($matches) {
                if (isset($matches[3])) {
                    return "\n" . $this->chop($matches[1], $matches[3]) . "\n";
                }
                else {
                    return "\n" . $this->chop($matches[1]) . "\n";
                }
            },
            $sections
        );
        $this->html .= $sections; 
        return true;
        // 
    }

    /**
     * Populate the spine table, requires load() and manifest()
     * (for $this->opf_dom)
     */
    private function spine()
    {
        // keep the flow of <spine>
        $nl = $this->opf_dom->getElementsByTagName('spine');
        foreach ($nl->item(0)->childNodes as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) continue;
            $idref = $node->getAttribute("idref");
            if (!isset($this->manifest[$idref])) {
                Log::warning(I18n::_('Epub.spine404', $this->file, $idref));
                continue;
            }
            // if content.opf is not in same folder as toc.ncx, possible problems
            $path = $this->manifest[$idref];
            $this->spine[basename($path)] = $path;
        }
    }

    /**
     * populate the manifest table
     */
    private function manifest()
    {
        $nl = $this->opf_dom->getElementsByTagName('manifest');
        foreach ($nl->item(0)->childNodes as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) continue;
            // test media-type ?
            $id = $node->getAttribute("id");
            $href = $node->getAttribute("href");
            $this->manifest[$id] = $href;
        }
    }




    /**
     * Get a toc in ncx format
     * requires load() for content.opf
     * set some properties
     */
    private function ncx_xml(): ?string
    {
        // <item href="toc.ncx" id="ncx" media-type="application/x-dtbncx+xml"/>
        $nl = $this->opf_xpath->query("//opf:item[@media-type='application/x-dtbncx+xml']");
        if (!$nl->length) {
            Log::warning(I18n::_('Epub.ncx400', $this->file, $this->opf_path));
            return null;
        }
        $ncx_path = $nl->item(0)->getAttribute("href");
        $ncx_path = urldecode($ncx_path);
        if ($ncx_path[0] != "/") $ncx_path = $this->opf_dir . $ncx_path;
        $this->ncx_dir = dirname($ncx_path);
        if ($this->ncx_dir == ".") $this->ncx_dir = "";
        else $this->ncx_dir .= "/";
        return $this->get($ncx_path);
    }



    /**
     * According to toc, cut a piece of html to insert in a section hierachy
     */
    public function chop(string $from, ?string $to = null)
    {
        $html = "";

        if ($to) $html .= "<!-- $from -> $to -->\n";
        else $html .= "<!-- $from -->\n";
        $from_file = $from;
        $from_anchor = "";
        if ($pos = strpos($from, '#')) {
            list($from_file, $from_anchor) = explode("#", $from);
        }
        $to_file = $to;
        $to_anchor = "";
        if ($to && ($pos = strpos($to, '#'))) {
            list($to_file, $to_anchor) = explode("#", $to);
        }
        if ($to_file) $to_file = $this->ncx_dir . urldecode($to_file);
        
        // text to insert should be from one file only
        $from_file = $this->ncx_dir . urldecode($from_file);
        $contents = $this->get($from_file);
        if (!$contents) {
            $html .= "<!-- $from_file not found -->\n";
            return $html;
        }
        // indent some blocks
        $contents = preg_replace(
            array('@(</(div|h1|h2|h3|h4|h5|h6|p)>)([^\n])@', '@(<body)@'),
            array("$1\n$3", "\n$1"),
            $contents
        );
        // gat the start index from wich insert
        $pos_start = 0;
        if (!preg_match('@<body[^>]*>@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $msg = I18n::_("Epub.chop.body", $this->file, $from_file);
            Log::warning($msg);
            $html .= "<!-- $msg -->\n";
        } else {
            $pos_start = $matches[0][1] + strlen($matches[0][0]);
        }
        if ($from_anchor) {
            // take start of line
            // <h1 class="P_Heading_1"><span><a id="auto_bookmark_1"/>PROLOGUE</span></h1>
            if (!preg_match('@\n.*id="' . $from_anchor . '"@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                $msg = I18n::_("Epub.chop.anchor", $this->file, $from_file, $from_anchor);
                Log::warning($msg);
                $html .= "<!-- $msg -->\n";
            } else {
                $pos_start = $matches[0][1];
            }
        }
        // search en index
        $pos_end = strlen($contents);
        if (preg_match('@</body>@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $pos_end = $matches[0][1];
        }
        // end anchor ?
        if ($from_file == $to_file) {
            // no anchor, bas toc, warn
            if (!$to_anchor) {
                $msg = I18n::_("Epub.chop.to.no_anchor", $this->file, $from_file, $from, $to);
                Log::warning($msg);
                $html .= "<!-- $msg -->\n";
            } 
            // anchor not found
            else if (!preg_match('@\n.*id="' . $to_anchor . '"@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                $msg = I18n::_("Epub.chop.to.anchor404", $this->file, $from_file, $from, $to, $to_anchor);
                Log::warning($msg);
                $html .= "<!-- $msg -->\n";
            } 
            // end index found
            else {
                $pos_end = $matches[0][1];
            }
        }
        $contents = substr($contents, $pos_start, $pos_end - $pos_start);
        // NO ! &
        // preserve some critic XML entities before transcoding
        $contents = preg_replace("@&(amp|lt|gt);@", "£$1;", $contents);
        $contents = html_entity_decode($contents, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $contents = preg_replace("@£(amp|lt|gt);@", "&$1;", $contents);
        // restore some entities before transcoding
        // $html = preg_replace( self::$rehtml[0], self::$rehtml[1], $html );
        // html usually need to be repaired, because of bad html fragments
        $contents = tidy_repair_string($contents, self::$tidyconf);

        $html .= $contents;

        return $html;
    }
}
