<?php
/**
 * @package nxcPromipool
 * @class   nxcViewsCount
 * @author  spi@nxc.no <Alex Pilyavskiy>
 * @date    26 Jun 2013
 **/

class nxcViewsCount extends eZPersistentObject {

    public $object = false;
    
    public function __construct( $row = array() ) {
        $this->eZPersistentObject( $row );
    }

    public static function definition() {
        return array(
            'fields'              => array(
                'id' => array(
                    'name'     => 'id',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'object_id' => array(
                    'name'     => 'object_id',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'views_count' => array(
                    'name'     => 'views_count',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => false
                )
            ),
            'keys'                => array( 'id' ),
            'sort'                => array( 'id' => 'desc' ),
            'increment_key'       => 'id',
            'class_name'          => 'nxcViewsCount',
            'name'                => 'nxc_viewscount'
        );
    }
	
    public static function fetch( $id ) {
        return eZPersistentObject::fetchObject(
            self::definition(),
            null,
            array( 'id' => $id ),
            true
        );
    }	
	
    static function removeObject( $id, $conditions = null, $extraConditions = null ) {
        return eZPersistentObject::removeObject(
            self::definition(),
            array( 'id' => $id ),
            null
        );
    }
	
    public static function fetchByObjectID( $objectID ) {
        $cond['object_id'] = $objectID;        
        $result =   eZPersistentObject::fetchObjectList(
            self::definition(),
            null,
            $cond,
            null,
            null,
            true
        );                        
        
        return $result;
    }

    public static function fetchByViews( $viewsCount ) {        
        if (!is_array($viewsCount)) $viewsCount = array( $viewsCount );
        $cond['views_count'] = $viewsCount;
        $result =   eZPersistentObject::fetchObjectList(
			self::definition(),
			null,
			$cond,
			null,
			null,
			true
		);
        return $result;
    }              
    
    public function store( $fieldFilters = null ) {
        eZPersistentObject::storeObject( $this, $fieldFilters );
        return $this;
    }
}
?>
