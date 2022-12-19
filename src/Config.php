<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte;

use Oeuvres\Kit\{I18n};

class Config
{

    /**
     * Initialize static variable
     */
    static public function init()
    {
        I18n::load(dirname(__DIR__) . '/teinte_en.tsv');

    }
}
Config::init();
