<?php

abstract class Image extends Kohana_Image{

    protected function getFilePath(){
        return $this->file;
    }

    public function toBase64(){
        $path = $this->getFilePath();
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
}