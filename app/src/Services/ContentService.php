<?php

namespace App\Services;

use App\Repositories\PageRepository;
use App\Services\ImageUploader;

class ContentService implements CMSService
{
    private PageRepository $repository;

    public function __construct(PageRepository $repository)
    {
        $this->repository = $repository;
    }
    public function getForEdit(int $id)
    {
        return $this->repository->getContentForEdit($id);
    }
    public function isNameEditable(): bool
    {
        return false;
    }
    public function getPageId(int $id)
    {
        return $this->repository->getContentPageId($id)['page_id'];
    }

    public function updateWithImage(int $id, array $postData, array $fileData): bool
    {
        $data = $postData;
        $data['id'] = $id;
        foreach ($fileData as $field => $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                // No new file uploaded — fall back to the existing filename
                if (isset($data[$field . '_existing']) && $data[$field . '_existing'] !== '') {
                    $data[$field] = $data[$field . '_existing'];
                }
                continue;
            }

            $filename = ImageUploader::handleImageUpload($file);
            $data[$field] = $filename;
        }
        $data = $this->stripExistingImageKeys($data);

        $component = $this->repository->getContentForEdit($id);
        if ($component->getName() === 'hero_banner') {
            $data = $this->normalizeHeroFields($data);
        } elseif ($component->getName() === 'split_content_block') {
            $data = $this->normalizeSplitContentBlockFields($data);
        } elseif ($component->getName() === 'tickets_passes') {
            $data = $this->normalizeTicketsPassesFields($data);
        } elseif ($component->getName() === 'venues_map') {
            $data = $this->normalizeVenuesMapFields($data);
        }

        return $this->repository->updateContentItem($id, $data);
    }
    public function update(int $id, array $postData): bool
    {
        $data = $this->resolveExistingImageFields($postData);
        $component = $this->repository->getContentForEdit($id);
        if ($component->getName() === 'hero_banner') {
            $data = $this->normalizeHeroFields($data);
        } elseif ($component->getName() === 'split_content_block') {
            $data = $this->normalizeSplitContentBlockFields($data);
        } elseif ($component->getName() === 'tickets_passes') {
            $data = $this->normalizeTicketsPassesFields($data);
        } elseif ($component->getName() === 'venues_map') {
            $data = $this->normalizeVenuesMapFields($data);
        }

        return $this->repository->updateContentItem($id, $data);
    }
    public function delete(int $id): bool
    {
        return $this->repository->deleteContentItem($id);
    }

    private function resolveExistingImageFields(array $data): array
    {
        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_existing')) {
                $field = substr($key, 0, -strlen('_existing'));
                if (!isset($data[$field]) || $data[$field] === '') {
                    $data[$field] = $value;
                }
            }
        }
        return $this->stripExistingImageKeys($data);
    }

    private function stripExistingImageKeys(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_ends_with($key, '_existing')) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    private function normalizeHeroFields(array $data): array
    {
        $heroBannerPlainTextFields = [
            'date_badge',
            'heading',
            'subheading',
            'primary_cta_label',
            'primary_cta_url',
            'secondary_cta_label',
            'secondary_cta_url',
            'scroll_target',
        ];

        foreach ($heroBannerPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizePlainText(string $value): string
    {
        $plainText = strip_tags($value);
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = str_replace("\xC2\xA0", ' ', $plainText);
        $plainText = preg_replace('/\s+/u', ' ', $plainText) ?? $plainText;

        return trim($plainText);
    }

    private function normalizeSplitContentBlockFields(array $data): array
    {
        $splitContentBlockPlainTextFields = [
            'heading',
            'body_text',
            'image_alignment',
        ];

        foreach ($splitContentBlockPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeTicketsPassesFields(array $data): array
    {
        $ticketsPassesPlainTextFields = [
            'section_id',
            'heading',
            'intro_text',
            'card_1_title',
            'card_1_price',
            'card_1_description',
            'card_1_cta_label',
            'card_1_cta_url',
            'card_1_badge',
            'card_2_title',
            'card_2_price',
            'card_2_description',
            'card_2_cta_label',
            'card_2_cta_url',
            'card_2_badge',
            'card_3_title',
            'card_3_price',
            'card_3_description',
            'card_3_cta_label',
            'card_3_cta_url',
            'card_3_badge',
            'note_1',
            'note_2',
        ];

        foreach ($ticketsPassesPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeVenuesMapFields(array $data): array
    {
        $venuesMapPlainTextFields = [
            'section_id',
            'heading',
            'intro_text',
            'map_image_alt',
            'location_1_name',
            'location_1_address',
            'location_1_description',
            'location_2_name',
            'location_2_address',
            'location_2_description',
        ];

        foreach ($venuesMapPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }
}
