<?php
/**
 * @package org.openpsa.products
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_list extends midcom_baseclasses_components_handler
implements client
{
    use org_openpsa_products_handler;

    private provider $provider;

    private ?datamanager $datamanager = null;

    private ?org_openpsa_products_product_group_dba $group = null;

    public function _on_initialize()
    {
        $this->provider = new provider($this);
    }

    public function get_qb(?string $field = null, string $direction = 'ASC', array $search = []) : midcom_core_query
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();
        if (!empty($this->group)) {
            $qb->add_constraint('productGroup', '=', $this->group->id);
        }

        if ($field !== null) {
            $qb->add_order($field, $direction);
        }

        foreach ($search as $key => $value) {
            if (in_array($key, ['code', 'title'], true)) {
                $qb->add_constraint($key, 'LIKE', $value . '%');
            } else {
                $qb->add_constraint($key, '=', $value);
            }
        }

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $object) : array
    {
        $link_html = '<a href="' . $this->router->generate('view_product', ['guid' => $object->guid]) . '">';

        return [
            'id' => $object->id,
            'index_code' => $object->code,
            'code' => $link_html . $object->code . '</a>',
            'index_title' => $object->title,
            'title' => $link_html . $object->title . '</a>',
            'price' => $object->price,
            'delivery' => $object->delivery,
            'orgOpenpsaObtype' => $object->orgOpenpsaObtype,
            'unit' => $object->unit
        ];
    }

    /**
     * @param string $guid The object's GUID
     */
    public function _handler_list(array &$data, $guid = null)
    {
        $data['data_url'] = $this->router->generate('list_json');
        if ($guid !== null) {
            $this->group = new org_openpsa_products_product_group_dba($guid);
            $data['view_title'] = $this->group->title;
            $data['data_url'] = $this->router->generate('list_json_group', ['guid' => $guid]);

            $this->datamanager = new datamanager($data['schemadb_group']);
            $this->datamanager->set_storage($this->group);
            $tmp = $this->update_breadcrumb_line($this->group);
            midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $tmp);
        } else {
            $data['view_title'] = $this->_l10n->get('product database');
        }
        $data['grid'] = $this->provider->get_grid('product_list');

        $this->_populate_toolbar();
        midcom::get()->head->set_pagetitle($data['view_title']);
        if ($this->datamanager) {
            $data['view_group'] = $this->datamanager->get_content_html();
        }

        return $this->show('list');
    }

    private function _populate_toolbar()
    {
        $allow_create_group = midcom::get()->auth->can_user_do('midgard:create', class: org_openpsa_products_product_group_dba::class);
        $allow_create_product = midcom::get()->auth->can_user_do('midgard:create', class: org_openpsa_products_product_dba::class);

        if (!empty($this->group)) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('edit_product_group', [
                'guid' => $this->group->guid
            ]), [
                MIDCOM_TOOLBAR_ENABLED => $this->group->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]));
        }

        $this->_add_schema_buttons('schemadb_group', 'cubes', '', $allow_create_group);
        $this->_add_schema_buttons('schemadb_product', 'cube', 'product/', $allow_create_product);

        if (!empty($this->group)) {
            $this->bind_view_to_object($this->group, $this->datamanager->get_schema()->get_name());
        }
    }

    private function _add_schema_buttons(string $schemadb_name, string $default_icon, string $prefix, bool $allowed)
    {
        $workflow = $this->get_workflow('datamanager');
        foreach ($this->_request_data[$schemadb_name]->all() as $name => $schema) {
            $config = [
                MIDCOM_TOOLBAR_GLYPHICON => $default_icon,
                MIDCOM_TOOLBAR_ENABLED => $allowed,
                MIDCOM_TOOLBAR_LABEL => sprintf(
                    $this->_l10n_midcom->get('create %s'),
                    $this->_l10n->get($schema->get('description'))
                ),
            ];
            if (isset($schema->get('customdata')['icon'])) {
                $config[MIDCOM_TOOLBAR_ICON] = $schema->get('customdata')['icon'];
            }
            $create_url = 'create/' . ($this->group->id ?? '0') . '/' . $name . '/';
            $this->_view_toolbar->add_item($workflow->get_button($prefix . $create_url, $config));
        }
    }

    /**
     * @param string $guid The object's GUID
     */
    public function _handler_json(array &$data, $guid = null)
    {
        if ($guid !== null) {
            $this->group = new org_openpsa_products_product_group_dba($guid);
        }
        midcom::get()->skip_page_style = true;
        $data['provider'] = $this->provider;

        return $this->show('list-json');
    }
}
