<?php
class HexStringLiteral extends StringLiteral implements Serializable {

    public function __construct($string) {
        parent::__construct($string);
        // TODO: set charset and collation
    }

    public function serialize() {
        return serialize(array('parentData' => parent::serialize()));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        parent::unserialize($data['parentData']);
    }
    
}
