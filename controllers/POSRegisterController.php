<?php
class POSRegisterController
{
    public function index()
    {
        $registers = '';
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