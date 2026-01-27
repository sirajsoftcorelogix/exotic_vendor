<?php
class POSRegisterController
{
    /**
     * Display a list of POS registers
     */
    public function index()
    {
        $registers = '';//$this->getRegisters();
        renderTemplate('views/pos_register/index.php', ['registers' => $registers]);        
    }
    
    /**
     * Helper method to fetch registers
     */
    private function getRegisters()
    {
        // TODO: Implement database query
        return [];
    }
}