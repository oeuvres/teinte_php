<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use DOMDocument;
use Oeuvres\Kit\{Filesys, Log};

/**
 * Load a TEI document and perform an url check on //ref/@target
 */
class Tei2checkurl extends AbstractTei2
{
    const EXT = '.xml';
    const NAME = "checkurl";
    /** A file is OK by default, except when a problem is found */
    private $ok = true;

    /**
     * Write transformation as an Uri, which is mainly, a file
     */
    static public function toUri(DOMDocument $dom, string $dst_file, ?array $pars=null) {
        $dom = self::toDoc($dom, $pars);
        if ($dom) $dom->save($dst_file);
    }
    /**
     * Export transformation as an XML string
     * (maybe not relevant for aggregated formats: docx, epub, site…)
     */
    static public function toXml(DOMDocument $dom, ?array $pars=null):?string
    {
        $dom = self::toDoc($dom, $pars);
        return $dom->saveXML();
    }
    /**
     * Verify DOM
     */
    static public function toDoc(DOMDocument $dom, ?array $pars=null):?DOMDocument 
    {

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'HEAD',
                    'timeout' => 2
                )
            )
        );
        $nl = $dom->getElementsByTagNameNS('http://www.tei-c.org/ns/1.0', 'ref');
        $att = "target";
        foreach ($nl as $link) {
            if (!$link->hasAttribute($att)) {
                Log::warning(
                    "\033[91m@target\033[0m attribute missing "
                    . self::message($link, $att, null)
                );
                continue;
            }
            $target = trim($link->getAttribute($att));
            // anchor link @xml:id, check dest
            if (substr($target, 0, 1) == '#') {
                $id = substr($target, 1);
                $el = $dom->getElementById($id);
                if ($el) continue;
                Log::error(
                    "\033[91mxml:id=\"$id\"\033[0m target element not found "
                    . self::message($link, $att, $target)
                );
                continue;
            }
            // absolute file link, bad
            else if (Filesys::isabs($target)) {
                Log::error(
                    "\033[91mabsolute file path\033[0m "
                    . self::message($link, $att, $target)
                );
                continue;
            }
            // url, test it (may be slow ?)
            else if (substr(trim($target), 0, 4) == 'http') {
                $headers = @get_headers($target);
                if (!$headers) {
                    Log::error(
                        "\033[91murl lost\033[0m "
                        . self::message($link, $att, $target)
                    );
                    continue;
                }
                preg_match("@\d\d\d@", $headers[0], $matches);
                $code = $matches[0];
                if ($code == '200' || $code == '302') {
                    Log::debug(
                        "\033[91m" . substr($headers[0], 9) . "\033[0m "
                        . self::message($link, $att, $target)
                    );
                    continue;
                }
                Log::error(
                    "\033[91m" . substr($headers[0], 9) . "\033[0m "
                    . self::message($link, $att, $target)
                );
                continue;
            }
            // relative link
            else { 
                Log::error(
                    "\033[91mfile not found\033[0m "
                    . self::message($link, $att, $target)
                );
                continue;
            }
        }
        return $dom;
    }
    /**
     * Format log message
     */
    private static function message($el, $att)
    {
        if (!$el) return "";
        $m = "";
        $m .= "l. {$el->getLineNo()} <{$el->nodeName}";
        // value ?
        if ($el->hasAttribute($att)) {
            $value = $el->getAttribute($att);
            $m .= " $att=\"$value\"";
            $cert = $el->getAttribute("cert");
            if ($cert) {
                $cert = preg_split("/ +/", $cert, PREG_SPLIT_NO_EMPTY );
            }
            else {
                $cert = [];
            }
            $cert['0'] = true;
            $el->setAttribute('cert', implode(' ', array_keys($cert)));
        }
        $m .= ">";
        return $m;
    }
}

// EOF