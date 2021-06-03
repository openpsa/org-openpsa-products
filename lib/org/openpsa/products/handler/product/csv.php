<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_csv extends midcom_baseclasses_components_handler_dataexport
{
    /**
     * @var midcom_services_session
     */
    private $session;

    public function _load_schemadbs(string $handler_id, array &$args, array &$data) : array
    {
        $this->session = new midcom_services_session('org_openpsa_products_csvexport');
        if (!empty($_POST)) {
            $this->session->set('POST_data', $_POST);
        }

        if (isset($args[0])) {
            $group_name_to_filename = '';
            if ($root_group_guid = $this->_config->get('root_group')) {
                $root_group = org_openpsa_products_product_group_dba::get_cached($root_group_guid);
                $group_name_to_filename = strtolower(str_replace(' ', '_', $root_group->code)) . '_';
            }
            $schemadb_to_use = str_replace('.csv', '', $args[0]);
            $data['filename'] = $group_name_to_filename . $schemadb_to_use . '_' . date('Y-m-d') . '.csv';
        } elseif (array_key_exists('org_openpsa_products_export_schema', $_POST)) {
            //We do not have filename in URL, generate one and redirect
            $data['filename'] = $_POST['org_openpsa_products_export_schema'];
            return [];
        } else {
            $schemadb_to_use = $this->_config->get('csv_export_schema');
        }

        $this->_schema = $this->_config->get('csv_export_schema');
        $schemadb = $this->_request_data['schemadb_product'];
        if ($schemadb->has($schemadb_to_use)) {
            $this->_schema = $schemadb_to_use;
        }

        return [$schemadb];
    }

    public function _load_data(string $handler_id, array &$args, array &$data) : array
    {
        if (   empty($_POST)
            && $this->session->exists('POST_data')) {
            $_POST = $this->session->get('POST_data');
            $this->session->remove('POST_data');
        }

        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_order('code');
        $qb->add_order('title');

        if ($root_group_guid = $this->_config->get('root_group')) {
            $root_group = org_openpsa_products_product_group_dba::get_cached($root_group_guid);
            if (empty($_POST['org_openpsa_products_export_all'])) {
                $qb->add_constraint('productGroup', '=', $root_group->id);
            } else {
                $qb_groups = org_openpsa_products_product_group_dba::new_query_builder();
                $qb_groups->add_constraint('up', 'INTREE', $root_group->id);
                $qb->begin_group('OR');
                $qb->add_constraint('productGroup', '=', $root_group->id);
                foreach ($qb_groups->execute() as $group) {
                    $qb->add_constraint('productGroup', '=', $group->id);
                }
                $qb->end_group();
            }
        }
        $products = [];
        foreach ($qb->execute() as $product) {
            if ($product->get_parameter('midcom.helper.datamanager2', 'schema_name')) {
                $products[] = $product;
            }
        }

        return $products;
    }
}
