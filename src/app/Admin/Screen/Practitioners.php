<?php

    /* > Changes
    - New Logic of reporting
    */

namespace Zorgportal\Admin\Screen;

use Zorgportal\Practitioners as Core;
use Zorgportal\App;

class Practitioners extends Screen
{
    public function render()
    {
        return $this->renderTemplate('practitioners.php', array_merge(Core::query([
            'current_page' => (int) ($_GET['p'] ?? ''),
            'search' => $_GET['search'] ?? '',
            'orderby' => $this->getActiveSort()['prop'] ?? '',
            'order' => $this->getActiveSort()['order'] ?? '',
        ]), [
            'getActiveSort' => [ $this, 'getActiveSort' ],
            'getNextSort' => [ $this, 'getNextSort' ],
            'nonce' => wp_create_nonce('zorgportal'),
        ]));
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_style( 'zportal-codes', "{$base}src/assets/codes.css", [], $this->appContext::SCRIPTS_VERSION );
        wp_enqueue_script( 'zportal-codes', "{$base}src/assets/codes.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
    }

    public function update()
    {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( isset( $_POST['delete_all'] ) ) {
            $del = Core::deleteAll();
            return $this->success( sprintf(
                _n( '%d practitioner deleted.', '%d practitioners deleted.', $del, 'zorgportal' ), $del
            ) );
        }

        $items = array_filter(array_unique( array_map('intval', (array) ($_POST['items'] ?? '')) ));

        if ( ! $items )
            return;

        if ( 'delete' == ( $_POST['action2'] ?? '' ) ) {
            $del = Core::delete($items);
            return $this->success( sprintf(
                _n( '%d practitioner deleted.', '%d practitioners deleted.', $del, 'zorgportal' ), $del
            ) );
        }
    }

    public function getActiveSort() : array
    {
        $sort = explode(',', (string) ( $_GET['sort'] ?? '' ));
        $prop = strtolower($sort[0] ?? '');
        $order = strtolower($sort[1] ?? '');

        if ( $prop && ! array_key_exists($prop, Core::COLUMNS) ) {
            $prop = '';
            $order = '';
        }

        $order = in_array($order, ['asc','desc']) ? $order : 'desc';
        $order = $prop ? $order : '';

        return compact('order', 'prop');
    }

    public function getNextSort( string $prop ) : string
    {
        $current = $this->getActiveSort();

        if ( $prop == $current['prop'] ) {
            return 'asc' !== $current['order'] ? 'asc' : 'desc';
        }

        return 'desc';
    }
}