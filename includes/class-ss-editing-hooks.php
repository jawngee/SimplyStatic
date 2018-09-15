<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class EditingHooks {
    private $delegate;

    public function __construct(Plugin $delegate) {
        $this->delegate=$delegate;

        add_action('edit_post', array(&$this, 'postEdited'), 99);
        add_action('transition_post_status', array(&$this,'postStatusChanged'),99, 3);
        add_action('deleted_post', array(&$this, 'postDeleted'), 99);

        // Full purges (theme changes, etc.)
        add_action('switch_theme', array(&$this, 'themeSwitched'), 99);
        add_action('update_option_sidebars_widgets', array(&$this, 'sidebarUpdated'), 99);
        add_action('widgets.php', array(&$this, 'widgetsChanged'), 99);
        add_action("update_option_theme_mods_".get_option('stylesheet'), array(&$this, 'themeChanged'), 99);

        // Links
        add_action("deleted_link",array(&$this, 'deletedLink'), 99);
        add_action("edit_link",array(&$this, 'editedLink'), 99);
        add_action("add_link",array(&$this, 'addedLink'), 99);

        // Categories
        add_action("edit_category",array(&$this, 'purgeCategory'), 99);
        add_action("edit_link_category",array(&$this, 'purgeLinkCategory'), 99);
        add_action("edit_post_tag",array(&$this, 'purgeTagCategory'), 99);
    }

    public function themeSwitched() {
        $this->delegate->siteStateChanged('Theme switched');
    }

    public function sidebarUpdated() {
        $this->delegate->siteStateChanged('Sidebar updated.');
    }

    public function widgetsChanged() {
        $this->delegate->siteStateChanged('Widgets changed.');
    }

    public function themeChanged() {
        $this->delegate->siteStateChanged('Widgets changed.');
    }

    public function postEdited($postId, $call=true) {
        $this->delegate->siteStateChanged('Post edited.',$postId);
    }

    public function postDeleted($postId, $call=true) {
        $this->delegate->siteStateChanged('Post deleted.',$postId);
    }

    public function postStatusChanged($new_status, $old_status, $post, $call=true) {
        $this->delegate->siteStateChanged('Post changed status.',$post->ID,null,$old_status,$new_status);
    }

    public function purgeCategories($postId, $call=true) {
        $this->delegate->siteStateChanged('Categories changed.',$postId);
    }

    public function deletedLink() {
        $this->delegate->siteStateChanged('Deleted link.');
    }

    public function addedLink() {
        $this->delegate->siteStateChanged('Added link.');
    }

    public function editedLink() {
        $this->delegate->siteStateChanged('Edited link.');
    }

    public function purgeCategory($categoryId, $call=true) {
        $this->delegate->siteStateChanged('Category changed.',null,$categoryId);
    }

    public function purgeLinkCategory($categoryId) {
        $this->delegate->siteStateChanged('Category link changed.',null,$categoryId);
    }

    public function purgeTagCategory($categoryId, $call=true) {
        $this->delegate->siteStateChanged('Post tag changed.',null,$categoryId);
    }
}