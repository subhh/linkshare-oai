<?php

declare(strict_types=1);

/**
 * This file is part of Linkshare OAI Webservice.
 *
 * Linkshare OAI Webservice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Linkshare OAI Webservice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Linkshare OAI Webservice.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace SubHH\Linkshare\OAI;

use HAB\OAI\PMH\ProtocolError;
use HAB\OAI\PMH\Model\ResponseBody;
use HAB\OAI\PMH\Model\ResponseBodyInterface;
use HAB\OAI\PMH\Model\Set;

/**
 * OAI PMH ListSets operation.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
final class ListSets extends Command
{
    use Resumable;

    public function execute () : ResponseBodyInterface
    {
        $completeListSize = $this->mapper->countSets();
        $sets = $this->mapper->findSets($this->cursor, $this->itemsPerPage);
        if (empty($sets)) {
            throw new ProtocolError\NoSetHierarchy();
        }
        $cursor = $this->cursor + count($sets);

        $response = new ResponseBody();
        foreach ($sets as $set) {
            $response->append($set);
        }

        $resumptionToken = $this->createResumptionToken($cursor, $completeListSize);
        if ($resumptionToken) {
            $response->setResumptionToken($resumptionToken);
        }

        return $response;
    }
}
