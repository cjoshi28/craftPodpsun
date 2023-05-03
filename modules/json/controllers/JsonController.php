<?php
namespace json\controllers;

use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\web\Controller;
use yii\web\Response;

class JsonController extends Controller
{
    protected array|bool|int $allowAnonymous = ['get-data', 'get-global-data'];

    public function actionGetData($slug): Response
    {

        try {
            $entry = Entry::find()
                ->slug($slug)
                ->one();

            if (!$entry) {
                throw new \yii\web\NotFoundHttpException ('Entry not found');
            }
            $data = $this->getFieldsOfEntry($entry);

            return $this->asJson($data);
        } catch (\yii\web\NotFoundHttpException $e) {
            return $this->asJson($e);
        }

    }

    public function actionGetGlobalData($slug): Response
    {
        try {
            $globalSet = GlobalSet::find()
                ->handle($slug)
                ->one();
            if (!$globalSet) {
                throw new \yii\web\NotFoundHttpException ('Global not found');
            }
            $data = $this->getFieldsOfEntry($globalSet);
            return $this->asJson($data);
        } catch (\yii\web\NotFoundHttpException $e) {
            return $this->asJson($e);
        }

    }

    public function getFieldsOfEntry($entry)
    {
        $data = [];
        $fieldLayout = $entry->getFieldLayout();

        foreach ($fieldLayout->getTabs() as $tab) {
            foreach ($tab->getElements() as $element) {
                if ($element instanceof \craft\fieldlayoutelements\CustomField ) {
                    $field = $element->getField();
                    
                    if ($field instanceof \craft\fields\Matrix ) {
                        $matrixData = $this->getMatrixFieldData($entry, $field);
                        $data[$field->handle] = $matrixData;
                    } else if ($field instanceof \craft\fields\Assets ) {
                        $assetsData = $this->getAssetsFieldData($entry, $field);
                        $data[$field->handle] = $assetsData;
                    } else if ($field instanceof \craft\fields\Entries ) {
                        $entriesData = $this->getEntriesFieldData($entry, $field);
                        $data[$field->handle] = $entriesData;
                    } else if ($field instanceof \craft\fields\Categories ) {
                        $categoriesData = $this->getCategoriesFieldData($entry, $field);
                        $data[$field->handle] = $categoriesData;
                    } else if($field instanceof \craft\fields\Tags){
                        $tagsData = $this->getTagsFieldData($entry, $field);
                        $data[$field->handle] = $tagsData;
                    }else {
                        $data[$field->handle] = $entry->getFieldValue($field->handle);
                    }
                }
            }
        }

        return $data;
    }

    public function getMatrixFieldData($entry, $field)
    {
        $matrixBlocks = $entry->getFieldValue($field->handle)->all();
        $matrixData = [];

        foreach ($matrixBlocks as $matrixBlock) {
            $blockData = [];

            foreach ($matrixBlock->getFieldLayout()->getTabs() as $blockTab) {
                foreach ($blockTab->getElements() as $blockElement) {
                    if ($blockElement instanceof \craft\fieldlayoutelements\CustomField ) {
                        $blockField = $blockElement->getField();

                        if ($blockField instanceof \craft\fields\Assets ) {
                            $assetData = $this->getAssetsFieldData($matrixBlock, $blockField);
                            $blockData[$blockField->handle] = $assetData;
                        } else if ($blockField instanceof \craft\fields\Entries ) {
                            $entryData = $this->getEntriesFieldData($matrixBlock, $blockField);
                            $blockData[$blockField->handle] = $entryData;
                        } else if ($blockField instanceof \craft\fields\Categories ) {
                            $categoriesData = $this->getCategoriesFieldData($entry, $blockField);
                            $blockData[$blockField->handle] = $categoriesData;
                        } else if($blockField instanceof \craft\fields\Tags){
                            $tagsData = $this->getTagsFieldData($entry, $blockField);
                            $blockData[$blockField->handle] = $tagsData;
                        }else {
                            $blockData[$blockField->handle] = $matrixBlock->getFieldValue($blockField->handle);
                        }
                    }
                }
            }

            $matrixData[] = $blockData;
        }

        return $matrixData;
    }

    public function getAssetsFieldData($entry, $field)
    {
        $assets = $entry->getFieldValue($field->handle);
        $assetData = [];

        foreach ($assets as $asset) {
            $assetData[] = [
                'url' => $asset->getUrl(),
                'title' => $asset->title,
                'alt' => $asset->title,
            ];
        }

        return $assetData;
    }

    public function getEntriesFieldData($entry, $field)
    {
        $relatedEntry = $entry->getFieldValue($field->handle)->one();

        if ($relatedEntry) {
            $entryData = $this->getFieldsOfEntry($relatedEntry);
            $entryData['slug'] = $relatedEntry->slug;
            return $entryData;
        } else {
            return null;
        }
    }

    public function getCategoriesFieldData($entry, $field)
    {
        $relatedCategories = $entry->{$field->handle}->all();
        $data = [];

        // Loop through the categories
        foreach ($relatedCategories as $category) {
            $categoryData = [
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'uri' => $category->uri,
            ];

            $data[] = $categoryData;
        }

        return $data;
    }

    public function getTagsFieldData($entry, $field)
    {
        $relatedTags = $entry->{$field->handle}->all();
        $data = [];
        foreach ($relatedTags as $tag) {
            $tagData = [
                'id' => $tag->id,
                'title' => $tag->title,
                'slug' => $tag->slug,
                'uri' => $tag->uri,
            ];

            $data[] = $tagData;
        }

        return $data;
    }

}
