<?php

namespace App\Services;

use App\Repositories\Interfaces\IPageRepository;
use App\Services\Interfaces\ITransactionManager;
use RuntimeException;
use Throwable;

class ContentService implements CMSServiceInterface
{
    private IPageRepository $repository;
    private ITransactionManager $transactions;

    public function __construct(IPageRepository $repository, ITransactionManager $transactions)
    {
        $this->repository = $repository;
        $this->transactions = $transactions;
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

            $filename = ImageUploader::upload($file);
            $data[$field] = $filename;
        }
        $data = $this->stripExistingImageKeys($data);

        $component = $this->repository->getContentForEdit($id);
        if ($component->getName() === 'hero_banner') {
            $data = $this->normalizeHeroFields($data);
        } elseif ($component->getName() === 'split_content_block') {
            $data = $this->normalizeSplitContentBlockFields($data);
        } elseif ($component->getName() === 'artist_hero') {
            $data = $this->normalizeArtistHeroFields($data);
        } elseif ($component->getName() === 'artist_story') {
            $data = $this->normalizeArtistStoryFields($data);
        } elseif ($component->getName() === 'artist_gallery') {
            $data = $this->normalizeArtistGalleryFields($data);
        } elseif ($component->getName() === 'artist_schedule') {
            $data = $this->normalizeArtistScheduleFields($data);
        } elseif ($component->getName() === 'artist_listening') {
            $data = $this->normalizeArtistListeningFields($data);
        } elseif ($component->getName() === 'artist_venues') {
            $data = $this->normalizeArtistVenuesFields($data);
        } elseif ($component->getName() === 'tickets_passes') {
            $data = $this->normalizeTicketsPassesFields($data);
        } elseif ($component->getName() === 'venues_map') {
            $data = $this->normalizeVenuesMapFields($data);
        }

        return $this->persistSanitized($id, $data);
    }
    public function update(int $id, array $postData)
    {
        $data = $this->resolveExistingImageFields($postData);
        $component = $this->repository->getContentForEdit($id);
        if ($component->getName() === 'hero_banner') {
            $data = $this->normalizeHeroFields($data);
        } elseif ($component->getName() === 'split_content_block') {
            $data = $this->normalizeSplitContentBlockFields($data);
        } elseif ($component->getName() === 'artist_hero') {
            $data = $this->normalizeArtistHeroFields($data);
        } elseif ($component->getName() === 'artist_story') {
            $data = $this->normalizeArtistStoryFields($data);
        } elseif ($component->getName() === 'artist_gallery') {
            $data = $this->normalizeArtistGalleryFields($data);
        } elseif ($component->getName() === 'artist_schedule') {
            $data = $this->normalizeArtistScheduleFields($data);
        } elseif ($component->getName() === 'artist_listening') {
            $data = $this->normalizeArtistListeningFields($data);
        } elseif ($component->getName() === 'artist_venues') {
            $data = $this->normalizeArtistVenuesFields($data);
        } elseif ($component->getName() === 'tickets_passes') {
            $data = $this->normalizeTicketsPassesFields($data);
        } elseif ($component->getName() === 'venues_map') {
            $data = $this->normalizeVenuesMapFields($data);
        }

        return $this->persistSanitized($id, $data);
    }
    public function delete(int $id)
    {
        $this->repository->deleteContentItem($id);
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

    private function persistSanitized(int $id, array $data): bool
    {
        unset($data['name'], $data['csrf_token']);

        foreach ($data as $key => $value) {
            $data[$key] = preg_replace('/^<[^>]+>|<\/[^>]+>$/', '', (string) $value);
        }

        try {
            $encodedJson = json_encode($data);
            if ($encodedJson === false) {
                throw new RuntimeException('Failed to encode content item data.');
            }

            return $this->transactions->run(
                fn(): bool => $this->repository->updateContentItem($id, $encodedJson)
            );
        } catch (Throwable $e) {
            error_log('ContentService::persistSanitized: ' . $e->getMessage());

            return false;
        }
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

    private function normalizeArtistHeroFields(array $data): array
    {
        $artistHeroPlainTextFields = [
            'artist_name',
            'artist_summary',
            'artist_location',
            'artist_genres',
            'featured_event_id',
            'featured_event_note',
            'tickets_cta_label',
            'tickets_cta_url',
            'artist_image_alt',
        ];

        foreach ($artistHeroPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeArtistStoryFields(array $data): array
    {
        $artistStoryPlainTextFields = [
            'section_id',
            'story_title',
            'paragraph_1',
            'paragraph_2',
            'paragraph_3',
            'quote_text',
            'quote_author',
            'highlights_title',
        ];

        foreach (range(1, 6) as $index) {
            $artistStoryPlainTextFields[] = 'highlight_' . $index . '_title';
            $artistStoryPlainTextFields[] = 'highlight_' . $index . '_text';
        }

        foreach ($artistStoryPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeArtistGalleryFields(array $data): array
    {
        $artistGalleryPlainTextFields = [
            'section_id',
            'card_1_image_alt',
            'card_1_caption',
            'card_2_image_alt',
            'card_2_caption',
        ];

        foreach ($artistGalleryPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeArtistScheduleFields(array $data): array
    {
        $artistSchedulePlainTextFields = [
            'section_id',
            'tickets_cta_url',
        ];

        foreach ($artistSchedulePlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeArtistListeningFields(array $data): array
    {
        $artistListeningPlainTextFields = [
            'section_id',
            'section_title',
        ];

        foreach (range(1, 4) as $index) {
            $artistListeningPlainTextFields[] = 'card_' . $index . '_image_alt';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_preview';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_badge';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_tracks_label';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_year_label';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_title';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_description';
            $artistListeningPlainTextFields[] = 'card_' . $index . '_featured';
        }

        foreach ($artistListeningPlainTextFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->normalizePlainText((string) $data[$field]);
        }

        return $data;
    }

    private function normalizeArtistVenuesFields(array $data): array
    {
        $artistVenuesPlainTextFields = [
            'section_id',
            'venues_title',
            'venues_subtitle',
            'map_title',
            'map_image_alt',
        ];

        foreach ($artistVenuesPlainTextFields as $field) {
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
