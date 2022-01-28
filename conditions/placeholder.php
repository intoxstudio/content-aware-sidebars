<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class CASConditionPlaceholder extends WPCAModule_Base
{
    /**
     * @param string $id
     * @param string $title
     * @param string $description
     * @param string $placeholder
     * @param string $category
     */
    public function __construct($id, $title, $description = '', $placeholder = '', $category = 'general')
    {
        parent::__construct($id, $title, $description, $placeholder);
        $this->category = $category;
    }

    /**
     * @inheritDoc
     */
    public function initiate()
    {
    }

    /**
     * @inheritDoc
     */
    public function list_module($list)
    {
        $list[] = [
            'id'            => $this->id,
            'text'          => $this->name,
            'placeholder'   => $this->placeholder,
            'default_value' => $this->default_value,
            'disabled'      => true,
        ];
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function db_join()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function save_data($post_id)
    {
    }

    /**
     * @inheritDoc
     */
    public function get_group_data($group_data, $post_id)
    {
        return $group_data;
    }

    /**
     * @inheritDoc
     */
    protected function _get_content($args = [])
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function in_context()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_context_data()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function __destruct()
    {
    }
}
