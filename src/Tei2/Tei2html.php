<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */


namespace Oeuvres\Teinte\Tei2;

/**
 * Export a TEI document as an html fragment <article>
 */

class Tei2html extends Tei2simple
{
    const EXT = '.html';
    const XSL = "tei_html.xsl";
    const NAME = "html";
    // TODO parameters for XSLT 

    /**
     * // where to  * find web assets like css and jslog for html file
     * if (!$theme) $theme = 'http://oeuvres.github.io/teinte/theme/'; 
     * find a good way to guess that
     */
}

// EOF