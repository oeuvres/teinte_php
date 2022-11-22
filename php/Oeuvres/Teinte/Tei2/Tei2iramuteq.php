<?php
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Teinte\Tei2;

/**
 * Export an XML/TEI document as text formatted for IRaMuteQ,
 * a text analysis platform.
 * http://www.iramuteq.org/
 */
class Tei2iramuteq extends Tei2simple
{
    const EXT = '_ira.txt';
    const NAME = 'iramuteq';
    const LABEL = 'IRaMuteQ (formatted text)';
    const XSL = "tei_txt/tei_iramuteq.xsl";
}

// EOF