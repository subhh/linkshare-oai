<?php

namespace SubHH\Linkshare\OAI;

use PDO;

abstract class Command
{
    /**
     * @var PDO
     */
    protected $dbh;

    public function __construct (PDO $dbh)
    {
        $this->dbh = $dbh;
    }

}
