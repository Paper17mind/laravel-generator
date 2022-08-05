<?php
namespace app;
use stdClass;

class ArrayObject
{
    function toObjects($array)
    {
        $object = new stdClass();
        $request = '$request->input';
        if (is_array($array)) {
            foreach ($array as $kolom => $isi) {
                $kolom = strtolower(trim($kolom));
                $object->$kolom = $request . '("' . $isi . '")';
            }
        }
        return $object;
    }
    function toObject($array)
    {
        $object = '';
        $request = '$request->input';
        if (is_array($array)) {
            foreach ($array as $kolom) {
                $object .=
                    '$dt->' .
                    $kolom->name .
                    ' = ' .
                    $request .
                    '("' .
                    $kolom->name .
                    '");';
            }
        }
        return $object;
    }
    /**Graphql processor */
    function toType($array, $name)
    {
        $object = '';
        if (is_array($array)) {
            foreach ($array as $kolom) {
                $type = ucfirst($kolom->typeData);
                $object .= "$kolom->name:$type! ";
            }
        }
        return $object;
    }
    function toMutation($array, $name)
    {
        $nm = $name;
        $type = $this->toType($array, $name);
        $inStore = "input inStore$nm {
            $type
        }";
        $inUpdate = "input inUpdate$nm {
            id: ID!
            $type
        }";
        $object = "
            create$nm (input: inStore$nm!): $nm @create
            update$nm (input: inUpdate$nm!): $nm @update
        ";
        return [
            'mutation' => $object,
            'input' => "
                $inStore
                $inUpdate
            ",
        ];
    }
    /*\endGraphql processor */
    function toArray($object)
    {
        $array = [];
        if (is_object($object)) {
            $array = get_object_vars($object);
        }
        return $array;
    }
}
