<?php
/**
 * FlexFaq - Flexible FAQ and product FAQ for PrestaShop
 *
 * Copyright 2017 Antonio Rossetti (https://www.kimengumi.fr)
 *
 * Licensed under the EUPL, Version 1.1 or – as soon they will be approved by
 * the European Commission - subsequent versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the Licence.
 * You may obtain a copy of the Licence at:
 *
 * https://joinup.ec.europa.eu/software/page/eupl
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions and
 * limitations under the Licence.
 */

require_once _PS_MODULE_DIR_ . 'flexfaq/classes/FlexFaqModel.php';

class AdminFlexfaqController extends ModuleAdminController {

	protected $position_identifier = 'id_flexfaq';

	public function __construct() {

		$this->context          = Context::getContext();
		$this->bootstrap        = true;
		$this->table            = 'flexfaq';
		$this->className        = 'FlexFaqModel';
		$this->identifier       = 'id_flexfaq';
		$this->_defaultOrderBy  = 'position';
		$this->lang             = true;
		$this->requiredDatabase = true;
		$this->addRowAction( 'edit' );
		$this->addRowAction( 'delete' );

		parent:: __construct();

		$this->bulk_actions = array(
			'delete' => array(
				'text'    => $this->l( 'Delete selected' ),
				'confirm' => $this->l( 'Delete selected items?' ),
				'icon'    => 'icon-trash'
			)
		);
		$this->fields_list  = array(
			$this->identifier => array(
				'title' => '#',
			),
			'title'           => array(
				'title' => $this->l( 'Title' ),
			),
			'position'        => array(
				'title'    => $this->l( 'Position' ),
				'position' => 'position',
			),
			'common'          => array(
				'title'  => $this->l( 'Common' ),
				'active' => 'status',
				'search' => false
			),
			'active'          => array(
				'title'  => $this->l( 'Enabled' ),
				'active' => 'status',
				'search' => false
			),
		);


	}

	public function renderForm() {
		$this->page_header_toolbar_title  .= ': Flex FAQ';
		$this->multiple_fieldsets         = true;
		$this->fields_value['products[]'] = $this->object->getAssociatedProducts();
		$this->fields_form[0]['form']     = array(
			'legend' => array(
				'title' => $this->l( 'FAQ' ),
			),
			'input'  => array(
				array(
					'name'     => 'active',
					'type'     => 'switch',
					'required' => $this->object::$definition['fields']['active']['required'],
					'label'    => $this->l( 'Enabled' ),
					'desc'     => $this->l( 'Enable or Disable the item' ),
					'is_bool'  => true,
					'values'   => array(
						array(
							'id'    => 'active_on',
							'value' => 1,
							'label' => $this->l( 'Enabled' )
						),
						array(
							'id'    => 'active_off',
							'value' => 0,
							'label' => $this->l( 'Disabled' )
						)
					)
				),
				array(
					'name'      => 'title',
					'type'      => 'text',
					'lang'      => $this->object::$definition['fields']['title']['lang'],
					'required'  => $this->object::$definition['fields']['title']['required'],
					'maxlength' => $this->object::$definition['fields']['title']['size'],
					'label'     => $this->l( 'Title' ),
					'desc'      => $this->l( 'Title / Question of the item' ),
				),
				array(
					'name'      => 'content',
					'type'      => 'textarea',
					'lang'      => $this->object::$definition['fields']['title']['lang'],
					'required'  => $this->object::$definition['fields']['content']['required'],
					'maxlength' => $this->object::$definition['fields']['content']['size'],
					'label'     => $this->l( 'Item content' ),
					'desc'      => $this->l( 'Main content for the item' ),
				),
			)
		);
		$this->fields_form[1]['form']     = array(
			'legend' => array(
				'title' => $this->l( 'Associations' ),
			),
			'input'  => array(
				array(
					'name'     => 'common',
					'type'     => 'switch',
					'required' => $this->object::$definition['fields']['common']['required'],
					'label'    => $this->l( 'Common' ),
					'desc'     => $this->l( 'Display item in the common FAQ page' ),
					'is_bool'  => true,
					'values'   => array(
						array(
							'id'    => 'common_on',
							'value' => 1,
							'label' => $this->l( 'Yes' )
						),
						array(
							'id'    => 'common_off',
							'value' => 0,
							'label' => $this->l( 'No' )
						)
					)
				),
				array(
					'name'     => 'products[]',
					'type'     => 'select',
					'multiple' => true,
					'class'    => 'chosen fixed-width-xxl',
					'label'    => $this->l( 'Associated Product(s)' ),
					'desc'     => $this->l( 'Select one or more associated products' ),
					'options'  => array(
						'query' => $this->object->getAssociableProducts(),
						'id'    => 'id_product',
						'name'  => 'name'
					)
				),
				array(
					'name'  => 'categories',
					'type'  => 'categories',
					'label' => $this->l( 'Associated categories' ),
					'desc'  => $this->l( 'Select one or more associated categories' ),
					'tree'  => array(
						'root_category'       => 1,
						'id'                  => 'id_category',
						'name'                => 'name_category',
						'use_checkbox'        => true,
						'selected_categories' => $this->object->getAssociatedCategories(),
					)
				),
			)
		);
		if ( Shop::isFeatureActive() ) {
			$this->fields_form[1]['form']['input'][] = array(
				'name'  => 'shops',
				'type'  => 'shop',
				'label' => $this->l( 'Associated shops' ),
				'desc'  => $this->l( 'Select one or more associated shops' ),
			);
		}

		foreach ( $this->fields_form as $key => $value ) {
			$this->fields_form[ $key ]['form']['submit'] = array( 'title' => $this->l( 'Save' ) );
		}

		return parent::renderForm();
	}

	public function ajaxProcessUpdatePositions() {
		$way        = (int) ( Tools::getValue( 'way' ) );
		$id_flexfaq = (int) ( Tools::getValue( 'id' ) );
		$positions  = Tools::getValue( $this->table );

		foreach ( $positions as $position => $value ) {
			$pos = explode( '_', $value );

			if ( isset( $pos[2] ) && (int) $pos[2] === $id_flexfaq ) {
				if ( $faq = new FlexFaqModel( (int) $pos[2] ) ) {
					if ( isset( $position ) && $faq->updatePosition( $way, $position ) ) {
						echo 'ok position ' . (int) $position . ' for faq ' . (int) $pos[1] . '\r\n';
					} else {
						echo '{"hasError" : true, "errors" : "Can not update faq ' . (int) $id_flexfaq . ' to position ' . (int) $position . ' "}';
					}
				} else {
					echo '{"hasError" : true, "errors" : "This faq (' . (int) $id_flexfaq . ') can t be loaded"}';
				}

				break;
			}
		}
	}
}