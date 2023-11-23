<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use Exception, DOMDocument, ZipArchive;
use Oeuvres\Teinte\Format\{Zip};
use Oeuvres\Kit\{Check, Log, Filesys, Xt};
use Oeuvres\Xsl\{Xpack};
Check::extension('zip');

/**
 * Output a MS.Word docx document from TEI.
 * code convention https://www.php-fig.org/psr/psr-12/
 */
class Tei2docx extends AbstractTei2
{
    const EXT = '.docx';
    const NAME = "docx";

    /** Some mapping between 3 char iso languange code to 2 char */
    const ISO639_3char = [
        'eng' => 'en',
        'fra' => 'fr',
        'lat' => 'la',    
    ];

    public static function init()
    {
        parent::init();
    }


    /**
     * Return a configured template or default
     */
    static private function template(?array $pars=null):string
    {
        if ($pars && isset($pars['template.docx'])) {
            return $pars['template.docx'];
        }
        return Xpack::dir() . '/tei_docx/template.docx';
    }

    /**
     * @ override
     */
    static function toDOM(DOMDocument $dom, ?array $pars=null):?\DOMDocument
    {
        Log::error(__METHOD__." dom export not relevant");
        return null;
    }
    /**
     * @ override
     */
    static function toXML(DOMDocument $dom, ?array $pars=null):?string
    {
        Log::error(__METHOD__." xml export not relevant");
        return null;
    }


    private static function images(DOMDocument $dom, Zip $zip)
    {
        $dom_dir = null;
        if ($dom->documentURI && $dom->documentURI !== getcwd()) {
            $dom_dir = dirname($dom->documentURI);
        }
        $count = 1;
        $nl = $dom->getElementsByTagNameNS('http://www.tei-c.org/ns/1.0', 'graphic');
        if (!$nl->count()) return;
        $pad = strlen('' . $nl->count());
        $found = false;
        foreach ($nl as $el) {
            $att = $el->getAttributeNode("url");
            if (!isset($att) || !$att || !$att->value) {
                continue;
            }
            $data = Filesys::loadURL($att->value, $dom_dir);
            if (!$data) {
                // something went wrong and should have been logged
                continue;
            }
            $image_path = "media/image_" 
                . str_pad(strval($count), $pad, '0', STR_PAD_LEFT)
                . '.' . $data['ext'];
            if ($zip->put('word/' . $image_path, $data['bytes']) === false) {
                // write failed
                continue;
            }
            // change attribute value
            $el->setAttribute("url", $image_path);
            $count++;
        }
    }

    /**
     * @ override
     */
    static function toURI($dom, $dst_file, ?array $pars=null)
    {
        Log::debug("Tei2" . static::NAME ." $dst_file");
        $template = self::template($pars);
        if (!Filesys::readable($template)) {
            throw new Exception("“{$template}” not readble as a template file");
        }
        if (!Filesys::copy($template, $dst_file)) {
            throw new Exception("“{$dst_file}” not writable. May be this destination is open in Ms.Word\n" . Log::last());
        }
        // documentURI works for xml loader from file
        // $name = pathinfo($dom->documentURI, PATHINFO_FILENAME);
        $zip = new Zip();
        if ($zip->open($dst_file) !== TRUE) {
            return false;
        }
        // import images
        self::images($dom, $zip);

        // get a default lang from the source TEI, set it in the style.xml
        $xpath = Xt::xpath($dom);
        $entries = $xpath->query("/*/@xml:lang");
        $lang = null;
        foreach ($entries as $node) {
            $lang = $node->value;
        }
        // template is supposed to have a default language
        if ($lang) {
            if (isset(self::ISO639_3char[$lang])) $lang = self::ISO639_3char[$lang];
            $name = 'word/styles.xml';
            $xml = $zip->get($name);
            $xml = preg_replace(
                '@<w:lang[^>]*/>@', 
                '<w:lang w:val="'. $lang . '"/>', 
                $xml
            );
            $zip->put($name, $xml);
        }


        $re_clean = array(
            '@(<w:rPr/>|<w:pPr/>)@' => '',
        );

        // extract template to 
        $templPath = tempnam(sys_get_temp_dir(), "teinte_docx_");
        $templPath = "file:///" 
            . str_replace(DIRECTORY_SEPARATOR, "/", $templPath);
        // $this->logger->debug(__METHOD__.' $templPath='.$templPath);

        /* No more support for comments
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_comments.xsl', 
            $dom,
        );
        $zip->addFromString('word/comments.xml', $xml);
        */

        // template may not contain images
        // Word is confused without image file types
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <!-- Needed for images -->
    <Default Extension="png" ContentType="image/png"/>
    <Default Extension="jpeg" ContentType="image/jpeg"/>
    <Default Extension="jpg" ContentType="image/jpeg"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/word/_rels/document.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/endnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.endnotes+xml"/>
    <Override PartName="/word/fontTable.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.fontTable+xml"/>
    <Override PartName="/word/footnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml"/>
    <Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
    <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
    <Override PartName="/word/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
    <Override PartName="/word/webSettings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.webSettings+xml"/>
</Types>
        ';
        $zip->put('[Content_Types].xml', $xml);
        // generation of word/document.xml needs some links
        // from template, especially for head and foot page.
        file_put_contents($templPath, $zip->get('word/document.xml'));
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx.xsl',
            $dom,
            array(
                'templPath' => $templPath,
            )
        );
        $xml = preg_replace(
            array_keys($re_clean), 
            array_values($re_clean), 
            $xml
        );
        $zip->put('word/document.xml', $xml);
        file_put_contents($dst_file . '.document.xml', $xml);

        // keep some relations in footnotes from template
        file_put_contents(
            $templPath, 
            $zip->get('word/_rels/document.xml.rels')
        );
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_rels.xsl',
            $dom,
            array(
                'templPath' => $templPath,
            )
        );
        $zip->put('word/_rels/document.xml.rels', $xml);


        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_fn.xsl',
            $dom,
        );
        $xml = preg_replace(
            array_keys($re_clean), 
            array_values($re_clean), 
            $xml
        );
        $zip->put('word/footnotes.xml', $xml);


        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_fnrels.xsl',
            $dom,
        );
        $zip->put('word/_rels/footnotes.xml.rels', $xml);
        // 
        if (!$zip->close()) {
            Log::error(
                "Tei2docx ERROR writing \033[91m$dst_file\033[0m\nMaybe an app has an handle on this file. Is this docx open in MS.Word or LibreO.Writer?"
            );
        }
    }

}

Tei2docx::init();