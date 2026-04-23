<?php

if (!function_exists('irb_render_table_pagination')) {
    function irb_render_table_pagination($containerId = 'irbTablePagination')
    {
        $safeId = htmlspecialchars($containerId, ENT_QUOTES, 'UTF-8');
        echo '<div class="irb-table-pagination" id="' . $safeId . '"></div>';
    }
}
