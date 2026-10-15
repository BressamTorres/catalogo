<?php

namespace App\Services;

use App\Exceptions\MiniErpApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MiniErpSalesService
{
    /**
     * @param  array{
     *     external_order_id: string,
     *     customer_id?: int,
     *     seller_id?: int,
     *     items: array<int, array{product_id: int, quantity: int}>
     * }  $payload
     * @return array{id: int, number: string, total: string, created: bool}
     */
    public function createSale(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.mini_erp.url'), '/');
        $token = (string) config('services.mini_erp.token');
        $businessId = (int) config('services.mini_erp.business_id');

        if ($baseUrl === '' || $token === '' || $businessId <= 0) {
            throw new MiniErpApiException('A integração com o Mini ERP não está configurada.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->withToken($token)
                ->withHeader('X-Business-ID', (string) $businessId)
                ->connectTimeout((int) config('services.mini_erp.connect_timeout', 3))
                ->timeout((int) config('services.mini_erp.timeout', 10))
                ->post('/api/catalog/sales', $payload);
        } catch (ConnectionException $exception) {
            throw new MiniErpApiException(
                'Não foi possível confirmar o pedido no Mini ERP. Tente novamente.',
                previous: $exception,
            );
        }

        if (! in_array($response->status(), [200, 201], true)) {
            throw $this->exceptionFor($response);
        }

        return $this->resultFrom($response);
    }

    private function exceptionFor(Response $response): MiniErpApiException
    {
        $message = match ($response->status()) {
            401 => 'O catálogo não conseguiu se autenticar no Mini ERP.',
            403 => 'O catálogo não possui permissão para registrar vendas no Mini ERP.',
            409 => $this->responseMessage(
                $response,
                'Este pedido já existe no Mini ERP com dados diferentes.',
            ),
            422 => $this->responseMessage(
                $response,
                'O Mini ERP recusou os dados do pedido. Revise o carrinho e tente novamente.',
            ),
            default => 'O Mini ERP não conseguiu processar o pedido. Tente novamente.',
        };

        return new MiniErpApiException($message, $response->status());
    }

    private function responseMessage(Response $response, string $fallback): string
    {
        $message = $response->json('message');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    /**
     * @return array{id: int, number: string, total: string, created: bool}
     */
    private function resultFrom(Response $response): array
    {
        $data = $response->json();

        if (
            ! is_array($data)
            || ! isset($data['id'], $data['number'], $data['total'], $data['created'])
            || ! is_numeric($data['id'])
            || ! is_scalar($data['number'])
            || ! is_numeric($data['total'])
            || ! is_bool($data['created'])
        ) {
            throw new MiniErpApiException('O Mini ERP retornou uma resposta inválida.');
        }

        return [
            'id' => (int) $data['id'],
            'number' => (string) $data['number'],
            'total' => (string) $data['total'],
            'created' => $data['created'],
        ];
    }
}
