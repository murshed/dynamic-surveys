<?php
if(!defined('ABSPATH')) exit;
?>

<div class="dynamic-surveys-container" id="dynamic-surveys-<?php echo esc_attr($survey->id); ?>">
    <?php if ($has_voted): ?>
        <div class="dynamic-surveys-results">
            <h3><?php echo esc_html($survey->question); ?></h3>
            <div class="dynamic-surveys-results-wrapper">
                <canvas id="dynamic-surveys-results-chart-<?php echo esc_attr($survey->id); ?>" 
                        data-results='<?php echo esc_attr(wp_json_encode($results)); ?>'></canvas>
            </div>
            <p class="dynamic-surveys-vote-message">
                <?php esc_html_e('Thank you for participating in this survey!', 'dynamic-surveys'); ?>
            </p>
        </div>
    <?php else: ?>
        <div class="dynamic-surveys-form">
            <h3><?php echo esc_html($survey->question); ?></h3>
            <form class="dynamic-surveys-vote-form" data-survey-id="<?php echo esc_attr($survey->id); ?>">
                <?php 
                $options = json_decode($survey->options);
                foreach ($options as $index => $option): 
                ?>
                    <div class="dynamic-surveys-option">
                        <input type="radio" 
                               name="survey_option" 
                               id="option-<?php echo esc_attr($survey->id); ?>-<?php echo esc_attr($index); ?>" 
                               value="<?php echo esc_attr($index); ?>"
                               required>
                        <label for="option-<?php echo esc_attr($survey->id); ?>-<?php echo esc_attr($index); ?>">
                            <?php echo esc_html($option); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="dynamic-surveys-submit-vote">
                    <?php esc_html_e('Submit Vote', 'dynamic-surveys'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div> 