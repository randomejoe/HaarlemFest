<!DOCTYPE html>
<?php use App\Models\CmsType; ?>
<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';
    ?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <div class='cms-item-container column'>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row">
                        <input type="text" id="name" name="name" class="form-input" value="<?php 
                        echo $item->getName();
                        ?>" <?php if (!$editable) { echo 'readonly';} ?> required>
                        <button type="submit" class="form-submit-button button">Save</button>
                        <?php 
                        if ($type == CmsType::Page) {
                            $fieldName = "page style";
                            $selectorItems = [
                                'None' => 'None',
                                'History' => 'History',
                            ]; 
                            
                            $style = $item->getStyle();
                            $labelText = "Style";
                            
                            try {
                                if (isset($style)) {
                                    $initialSelection = $selectorItems[$style];
                                }
                                else {
                                    $initialSelection = $selectorItems['None'];
                                }
                            }
                            catch (Ex $e) {
                                $initialSelection = $selectorItems['None'];
                            }
                            
                            

                            require __DIR__ . '/../partials/cms/form_selector.php';
                        }
                        
                        ?>
                    </div>
                    <?php 
                        if ($type != CmsType::Page ) {
                            require(__DIR__ . '/../partials/cms/edit_' . $type->value . '.php');
                        }
                        ?></form><?php
                        if ($type == CmsType::Page) {
                            require(__DIR__ . '/../partials/cms/edit_' . $type->value . '.php');
                        }
                    ?>
            </div>
        </div>
    </div>
</div>