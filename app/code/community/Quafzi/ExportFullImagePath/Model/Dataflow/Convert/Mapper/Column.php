<?php
class Quafzi_ExportFullImagePath_Model_Dataflow_Convert_Mapper_Column extends Mage_Dataflow_Model_Convert_Mapper_Column
{
    public function map()
    {
        if (false === Mage::getStoreConfig('exportcsv/dataflow/fullimagepath')) {
            return parent::map();
        }
        $batchModel  = $this->getBatchModel();
        $batchExport = $this->getBatchExportModel();

        $batchExportIds = $batchExport
            ->setBatchId($this->getBatchModel()->getId())
            ->getIdCollection();

        $onlySpecified = (bool)$this->getVar('_only_specified') === true;

        if (!$onlySpecified) {
            foreach ($batchExportIds as $batchExportId) {
                $batchExport->load($batchExportId);
                $batchModel->parseFieldList($batchExport->getBatchData());
            }

            return $this;
        }

        if ($this->getVar('map') && is_array($this->getVar('map'))) {
            $attributesToSelect = $this->getVar('map');
        }
        else {
            $attributesToSelect = array();
        }

        if (!$attributesToSelect) {
            $this->getBatchExportModel()
                ->setBatchId($this->getBatchModel()->getId())
                ->deleteCollection();

            throw new Exception(Mage::helper('dataflow')->__('Error in field mapping: field list for mapping is not defined.'));
        }

        foreach ($batchExportIds as $batchExportId) {
            $batchExport = $this->getBatchExportModel()->load($batchExportId);
            $row = $batchExport->getBatchData();

            $newRow = array();
            foreach ($attributesToSelect as $field => $mapField) {
                ////// THIS IS THE ONLY CHANGE....
                if ('image' === $field) {
                    $newRow[$mapField] = isset($row[$field]) ? Mage::getBaseUrl('media') . 'catalog/product' . $row[$field] : null;
                } else {
                    $newRow[$mapField] = isset($row[$field]) ? $row[$field] : null;
                }
                ////// END OF CHANGE
            }

            $batchExport->setBatchData($newRow)
                ->setStatus(2)
                ->save();
            $this->getBatchModel()->parseFieldList($batchExport->getBatchData());
        }

        return $this;
    }
}
