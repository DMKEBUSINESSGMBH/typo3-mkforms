<?php

declare(strict_types=1);

namespace DMK\Mkforms\Middleware;

/***************************************************************
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Class ContentReplacer.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class HeaderInjection implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (isset($GLOBALS['tx_ameosformidable']) && isset($GLOBALS['tx_ameosformidable']['headerinjection'])) {
            reset($GLOBALS['tx_ameosformidable']['headerinjection']);
            foreach ($GLOBALS['tx_ameosformidable']['headerinjection'] as $aHeaderSet) {
                $body = new Stream('php://temp', 'rw');
                $body->write(str_replace(
                    $aHeaderSet['marker'],
                    implode("\n", $aHeaderSet['headers'])."\n".$aHeaderSet['marker'],
                    (string) $response->getBody()
                ));

                $response = $response->withBody($body);
            }
        }

        return $response;
    }
}
