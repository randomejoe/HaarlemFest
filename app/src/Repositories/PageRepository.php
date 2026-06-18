<?php

namespace App\Repositories;

use PDO;
use App\Models\Page;
use App\Models\PageContent;
use App\Repositories\Interfaces\IPageRepository;

class PageRepository implements IPageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPages(): array
    {
        $stmt = $this->pdo->prepare('SELECT title, page_id, is_main_event, style FROM pages');
        $stmt->execute();
        $pages = $stmt->fetchAll();

        $returnPages = [];
        foreach ($pages as $page) {
            $returnPages[] = Page::fromArray($page);
        }

        return $returnPages;
    }

    public function getContentPageId(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT page_id FROM page_content WHERE content_id = :content_id');
        $stmt->execute(['content_id' => $id]);
        $pageId = $stmt->fetch();
        return $pageId;
    }

    public function getPageById(int $id): Page
    {
        return $this->getPageBy('WHERE pages.page_id = :id', ['id' => $id]);
    }

    public function getPageByName(string $name): Page
    {
        return $this->getPageBy('WHERE LOWER(title) = LOWER(:title)', ['title' => $name]);
    }

    // to reduce code duplication
    private function getPageBy(string $whereStatement, array $params): Page {
        $stmt = $this->pdo->prepare('
            SELECT title, page_id, is_main_event, style FROM pages ' . $whereStatement
        );
        $stmt->execute($params);
        $pageData = $stmt->fetch();
        
        if (!$pageData) {
            return Page::invalidPage();
        }
        else {
            $page = Page::fromArray($pageData);
        }

        $stmt = $this->pdo->prepare(
            'SELECT component_name, data
            FROM page_content
            WHERE page_id = :page_id
            ORDER BY content_id ASC'
        );

        $stmt->execute(['page_id' => $pageData['page_id']]);

        $rows = $stmt->fetchAll();

        $pageContent = array_map(
        fn($row) => PageContent::fromArray($row),
            $rows
        );
        $page->setContent($pageContent);

        return $page;
    }

    public function createPage(string $title, int $isMainEvent)
    {
        $stmt = $this->pdo->prepare('INSERT INTO pages (title, is_main_event) VALUES (:title, :mainEvent)');
        $stmt->execute([
            'title' => $title,
            'mainEvent' => $isMainEvent
        ]);
    }

    public function getPageForEdit(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.title, pc.content_id, pc.component_name, pc.data, p.style
            FROM pages p
            LEFT JOIN page_content pc ON p.page_id = pc.page_id
            WHERE p.page_id = :page_id"
        );
        $stmt->execute(['page_id' => $id]);
        $pageContent = $stmt->fetchAll();

        // No page found to edit
        if ($pageContent == []) {
            return null;
        }

        $page = ['page_id' => $id, 'title' => $pageContent[0]['title'], 'content' => [], 'style' => $pageContent[0]['style']];
        foreach ($pageContent as $contentItem) {
            if (isset($contentItem['component_name'])) {
                $page['content'][] = PageContent::fromArray($contentItem);
            }
        }

        return Page::fromArray($page);
    }

    public function getContentForEdit(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT content_id, page_id, component_name, data
            FROM page_content pc
            WHERE pc.content_id = :content_id"
        );
        $stmt->execute(['content_id' => $id]);
        $component = $stmt->fetch();
        return PageContent::fromArray($component);
    }

    public function updatePage(int $id, array $data)
    {
        // Update page name and style

        if (isset($data['page_style']) && $data['page_style'] == 'None') {
            $data['page_style'] = null;
        }

        $stmt = $this->pdo->prepare("UPDATE pages SET title = :title, style = :style WHERE page_id = :id");
        $stmt->execute([
            'id' => $id,
            'title' => $data['name'],
            'style' => $data['page_style'] ?? null,
        ]);
    }

    public function addContentItemToPage(int $pageId, string $componentName)
    {
        $stmt = $this->pdo->prepare("INSERT INTO page_content (page_id, component_name) VALUES (:page_id, :component_name)");
        $stmt->execute([
            'page_id' => $pageId,
            'component_name' => $componentName,
        ]);
    }

    public function updateContentItem(int $id, string $encodedJson): bool
    {
        $stmt = $this->pdo->prepare("UPDATE page_content SET data = :data WHERE content_id = :id");
        return $stmt->execute([
            'id' => $id,
            'data' => $encodedJson,
        ]);
    }

    public function deletePage(int $pageId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM pages WHERE page_id = :page_id");
        $stmt->execute([
            'page_id' => $pageId
        ]);
    }

    public function deleteContentItem(int $contentId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM page_content WHERE content_id = :content_id");
        $stmt->execute([
            'content_id' => $contentId
        ]);
    }

    public function getEventCategories()
    {
        $stmt = $this->pdo->prepare('SELECT title as category FROM pages WHERE is_main_event = True');
        $stmt->execute();
        $categories = $stmt->fetchAll();
        return $categories;
    }
}
