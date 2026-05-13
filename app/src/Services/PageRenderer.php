<?php

namespace App\Services;

use App\Models\Page;

class PageRenderer
{
    private \Closure $resolve;

    public function __construct(\Closure $resolve)
    {
        $this->resolve = $resolve;
    }

    public function renderPage(Page $page) {
        require __DIR__ . '/../Views/partials/cms/component_registry.php';

        foreach($page->getContent() as $contentItem) {
            if (isset($components[$contentItem->getName()]['methods'])) {
                $methods = $components[$contentItem->getName()]['methods'];
                foreach ($methods as $method) {
                    ['service' => $requestedService, 'method' => $methodName, 'name' => $arrayKeyName] = $method;
                    $params = $method['parameters'] ?? [];

                    $service = ($this->resolve)($requestedService);

                    $params = $this->resolveParams($params, $page, $contentItem->getData());

                    if (!method_exists($service, $methodName)) {
                        throw new \RuntimeException("Method not found");
                    }
                    $result = $service->{$methodName}(...($params ?? []));
                    if (is_object($result) && method_exists($result, 'toArray')) {
                        $result = $result->toArray();
                    }
                    $contentItem->appendData([$arrayKeyName => $result]);
                }
            } 
        }

        require __DIR__ . '/../Views/dynamicPage.php';
    }

    private function resolveParams(array $params, page $page, array $data) {
        // For dynamic parameters such as page title

        $newParams = [];
        foreach ($params as $index => $param) {
            if (str_starts_with($param, 'data.')) {
                require_once __DIR__ . '/../Views/helpers.php';

                $newParams[] = hf_data($data, str_replace('data.', '', $param));
            }
            else {
                $newParams[] =  match ($param) {
                    'page.title' => $page->getName(),
                    default => $param,
                };
            }
        }

        return $newParams;
    }
}
