<?php

declare(strict_types=1);

namespace DMK\MkForms\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Core\Http\NullResponse;

/***************************************************************
 * Copyright notice
 *
 * (c) 2020 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

/**
 * Class AjaxHandler.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class AjaxHandler implements MiddlewareInterface
{
    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(DispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $mkformsAjaxId = $request->getParsedBody()['mkformsAjaxId'] ?? $request->getQueryParams()['mkformsAjaxId'] ?? null;

        if (null === $mkformsAjaxId) {
            return $handler->handle($request);
        }

        // Remove any output produced until now
        ob_clean();

        $request = $request->withAttribute('target', \formidableajax::class.'::run');

        return $this->dispatcher->dispatch($request) ?? new NullResponse();
    }
}
