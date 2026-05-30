<?php

/**
 * All courier partners (Aramex, Delhivery, DHL, Shiprocket, …) implement this interface.
 * Controllers must NOT call adapters directly — use CourierGateway.
 */
interface CourierAdapterInterface
{
    /** Lowercase partner code matching courier_partners.partner_code, e.g. aramex, delhivery */
    public function partnerCode(): string;

    /**
     * @param array<string, mixed> $request Built by CourierDispatchService::buildRateRequest()
     * @return array{success:bool,couriers?:list<array<string,mixed>>,message?:string,debug?:array<string,mixed>}
     */
    public function getRates(array $request): array;

    /**
     * @param array<string, mixed> $request Shipment context + selected quote metadata
     * @return array{success:bool,awb?:string,label_url?:string,partner_shipment_id?:string,message?:string,debug?:array<string,mixed>,data?:mixed}
     */
    public function createShipment(array $request): array;
}
