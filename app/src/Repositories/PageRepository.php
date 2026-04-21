<?php

namespace App\Repositories;

use PDO;
use App\Models\Page;
use App\Models\PageContent;

class PageRepository extends BaseRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPages(): array
    {
        $stmt = $this->pdo->prepare('SELECT title, page_id, is_main_event FROM pages');
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
        $stmt = $this->pdo->prepare(
            'SELECT title, pages.page_id, is_main_event, pc.component_name, pc.data
            FROM pages
            JOIN page_content pc ON pc.page_id = pages.page_id
            ' . $whereStatement
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $pageContent = array_map(
        fn($row) => PageContent::fromArray($row),
            $rows
        );
        $page = Page::fromArray($rows[0]);
        $page->setContent($pageContent);

        return $page;
    }

    public function createPage(string $title, int $isMainEvent): bool
    {
        $this->requireAdmin();
        $stmt = $this->pdo->prepare('INSERT INTO pages (title, is_main_event) VALUES (:title, :mainEvent)');
        $stmt->execute([
            'title' => $title,
            'mainEvent' => $isMainEvent
        ]);
        return true;
    }

    public function getPageForEdit(int $id)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare(
            "SELECT p.title, pc.content_id, pc.component_name, pc.data
            FROM pages p
            LEFT JOIN page_content pc ON p.page_id = pc.page_id
            WHERE p.page_id = :page_id"
        );
        $stmt->execute(['page_id' => $id]);
        $pageContent = $stmt->fetchAll();

        // No page found to edit
        if ($pageContent == null) {
            header('Location: /cms/pages');
            return null;
        }

        $page = ['page_id' => $id, 'title' => $pageContent[0]['title'], 'content' => []];
        foreach ($pageContent as $contentItem) {
            if (isset($contentItem['component_name'])) {
                $page['content'][] = PageContent::fromArray($contentItem);
            }
        }

        return Page::fromArray($page);
    }

    public function getContentForEdit(int $id)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare(
            "SELECT content_id, page_id, component_name, data
            FROM page_content pc
            WHERE pc.content_id = :content_id"
        );
        $stmt->execute(['content_id' => $id]);
        $component = $stmt->fetch();
        return PageContent::fromArray($component);
    }

    public function updatePage(int $id, array $data): bool
    {
        $this->requireAdmin();

        // Update page name
        $stmt = $this->pdo->prepare("UPDATE pages SET title = :title WHERE page_id = :id");
        $stmt->execute([
            'id' => $id,
            'title' => $data['name'],
        ]);
        return true;
    }
    public function addContentItemToPage(int $pageId, string $componentName)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare("INSERT INTO page_content (page_id, component_name) VALUES (:page_id, :component_name)");
        $stmt->execute([
            'page_id' => $pageId,
            'component_name' => $componentName,
        ]);
        return true;
    }
    public function updateContentItem(int $id, array $data): bool
    {
        $this->requireAdmin();

        try {
            $this->pdo->beginTransaction();
            unset($data['name']);
            unset($data['csrf_token']);

            foreach ($data as $key => $dataItem) {
                $data[$key] = preg_replace('/^<[^>]+>|<\/[^>]+>$/', '', $dataItem);
            }

            // Update content data
            $stmt = $this->pdo->prepare("UPDATE page_content SET data = :data WHERE content_id = :id");
            $stmt->execute([
                'id' => $id,
                'data' => json_encode($data),
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            echo $e;
            $this->pdo->rollback();
            return false;
        }
    }
    public function deletePage(int $pageId)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare("DELETE FROM pages WHERE page_id = :page_id");
        $stmt->execute([
            'page_id' => $pageId
        ]);
        return true;
    }
    public function deleteContentItem(int $contentId)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare("DELETE FROM page_content WHERE content_id = :content_id");
        $stmt->execute([
            'content_id' => $contentId
        ]);
        return true;
    }

    public function getEventCategories()
    {
        $stmt = $this->pdo->prepare('SELECT title as category FROM pages WHERE is_main_event = True');
        $stmt->execute();
        $categories = $stmt->fetchAll();
        return $categories;
    }
}
