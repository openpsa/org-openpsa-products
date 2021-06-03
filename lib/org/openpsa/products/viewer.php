<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * This is the class that defines which URLs should be handled by this module.
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_viewer extends midcom_baseclasses_components_viewer
{
    /**
     * Indexes a product
     *
     * @param midcom_db_topic|midcom_core_dbaproxy $topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index(datamanager $dm, midcom_services_indexer $indexer, $topic, $config = null)
    {
        if ($config == null) {
            $config = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config');
        }
        $object = $dm->get_storage()->get_value();

        $document = $indexer->new_document($dm);
        if (   $config->get('enable_scheduling')
            && is_a($object, org_openpsa_products_product_dba::class)) {
            // Check start/end for products
            if (   $object->start > time()
                || (   $object->end != 0
                    && $object->end < time())) {
                // Not in market, remove from index
                $indexer->delete($document->RI);
                return;
            }
            // FIXME: add midcom at job or somesuch to reindex products after their end time (and start time if in the future)
        }

        $document->topic_guid = $topic->guid;
        $document->component = $topic->component;
        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->content = "{$dm->get_schema()->get_name()} {$dm->get_schema()->get('description')} {$document->content}";
        $indexer->index($document);
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     */
    private function _populate_node_toolbar()
    {
        $buttons = [];
        if ($this->_topic->can_do('midgard:update')) {
            if ($this->_topic->can_do('midgard:create')) {
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => 'export/product/csv/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('export products'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n->get('export products'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'download',
                ];
            }
            if ($this->_topic->can_do('midcom:component_config')) {
                $workflow = $this->get_workflow('datamanager');
                $buttons[] = $workflow->get_button('config/', [
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
                ]);
            }
        }
        $this->_node_toolbar->add_items($buttons);
    }

    /**
     * The handle callback populates the toolbars and root group information.
     */
    public function _on_handle($handler, array $args)
    {
        if ($this->_config->get('root_group') === 0) {
            $this->_request_data['root_group'] = 0;
        } else {
            $root_group = org_openpsa_products_product_group_dba::get_cached($this->_config->get('root_group'));
            $this->_request_data['root_group'] = $root_group->id;
        }
        $this->_request_data['schemadb_group'] = schemadb::from_path($this->_config->get('schemadb_group'));
        $this->_request_data['schemadb_product'] = schemadb::from_path($this->_config->get('schemadb_product'));

        $this->_populate_node_toolbar();
    }
}
