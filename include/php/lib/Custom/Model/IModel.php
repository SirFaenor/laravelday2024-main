<?php
/**
 * @author Emanuele Fornasier
 */
namespace Custom\Model;

interface IModel {

   
    public function setProperty($propertyName, $value);
    
    public function getProperty($propertyName);
    
    public function getAllProperties();


}