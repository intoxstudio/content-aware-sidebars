<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class CASConditionPlaceholder extends WPCAModule_Base
{
    public function __construct($id, $title, $description = '', $placeholder = '', $category = 'general')
    {
        parent::__construct($id, $title, $description, $placeholder);
        $this->category = $category;
    }

    /**
     * @since 3.9
     *
     * @return void
     */
    public function initiate()
    {
    }

    /**
     * @since 3.9
     * @param array $list
     *
     * @return array
     */
    public function list_module($list)
    {
        $list[] = array(
            'id'            => $this->id,
            'text'          => $this->name,
            'placeholder'   => $this->placeholder,
            'default_value' => $this->default_value,
            'disabled'      => true,
        );
        return $list;
    }

    /**
     * @since 3.9
     *
     * @return string
     */
    public function db_join()
    {
        return '';
    }

    /**
     * @since 3.9
     * @param int $post_id
     *
     * @return void
     */
    public function save_data($post_id)
    {
    }

    /**
     * @since 3.9
     * @param array $group_data
     * @param int $post_id
     *
     * @return array
     */
    public function get_group_data($group_data, $post_id)
    {
        return $group_data;
    }

    /**
     * @since 3.9
     * @param array $args
     *
     * @return void
     */
    protected function _get_content($args = array())
    {
        return array();
    }

    /**
     * @since 1.0
     *
     * @return boolean
     */
    public function in_context()
    {
        return false;
    }

    /**
     * @since 3.9
     *
     * @return array
     */
    public function get_context_data()
    {
        return array();
    }

    /**
     * @since 3.9
     * @param array $posts
     *
     * @return array
     */
    public function filter_excluded_context($posts)
    {
        return $posts;
    }

    /**
     * @since 3.9
     */
    public function __destruct()
    {
    }
}
