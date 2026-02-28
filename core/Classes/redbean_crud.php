<?php


namespace Core;


class redbean_crud
{

    public function create($data) {
        return R::dispense('tablename')->import($data);
    }

    public function read($id) {
        return R::load('tablename', $id);
    }

    public function update($id, $data) {
        $record = R::load('tablename', $id);
        $record->import($data);
        R::store($record);
    }

    public function delete($id) {
        $record = R::load('tablename', $id);
        R::trash($record);
    }
}

?>