<?php

namespace SubHH\Linkshare\OAI;

use HAB\OAI\PMH\Model;

class Identify extends Command
{
    public function execute () : Model\Identity
    {
        $stmt = $this->dbh->query('select date(min(geaendert)) from quelle where freigeschaltet is not null');
        $earliestDatestamp = $stmt->fetchColumn();
        
        $identity = new Model\Identity();
        $identity->__set('baseURL', 'https://linkshare.sub.uni-hamburg.de/service/oai');
        $identity->__set('repositoryName', 'SUBHH Linkshare');
        $identity->__set('adminEmail', 'david.maus@sub.uni-hamburg.de');
        $identity->__set('earliestDatestamp', $earliestDatestamp);
        $identity->__set('deletedRecord', 'no');
        $identity->__set('granularity', 'YYYY-MM-DD');
        return $identity;
    }
}
