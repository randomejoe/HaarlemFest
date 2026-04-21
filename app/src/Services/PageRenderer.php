<?php

namespace App\Services;

use App\Container;
use App\Models\Page;

class PageRenderer
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function renderPage(Page $page) {
        require __DIR__ . '/../Views/partials/cms/component_registry.php';

        foreach($page->getContent() as $contentItem) {
            if (isset($components[$contentItem->getName()]['methods'])) {
                $methods = $components[$contentItem->getName()]['methods'];
                foreach ($methods as $method) {
                    $service = $this->container->get($method['service']);

                    if (!method_exists($service, $method['method'])) {
                        throw new \RuntimeException("Method not found");
                    }
                    $contentItem->appendData([$method['name'] => $service->{$method['method']}(...($method['parameters'] ?? []))]);
                }
            } 
        }

        require __DIR__ . '/../Views/dynamicPage.php';
    }
}

