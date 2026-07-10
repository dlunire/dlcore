<?php

declare(strict_types=1);

namespace DLCore\Licensing;

/**
 * Lee el estado del programa Fundadores (20 cupos Freelance gratis).
 */
final class FundadoresProgram {

    private const DEFAULT_TOTAL = 20;

    /**
     * @return array{
     *     fundadores_total: int,
     *     fundadores_usado: int,
     *     fundadores_restantes: int,
     *     fundadores_activo: bool,
     *     fundadores_codigo: string
     * }
     */
    public static function welcome_vars(?string $dataFile = null): array {
        $file = $dataFile ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'fundadores.json';

        $total = self::DEFAULT_TOTAL;
        $usado = 0;
        $codigo = 'DLUNIRE-FUNDADOR';

        if (is_readable($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);

            if (is_array($decoded)) {
                $total = (int) ($decoded['cupo_total'] ?? $total);
                $usado = (int) ($decoded['cupo_usado'] ?? $usado);
                $codigo = (string) ($decoded['programa'] ?? $codigo);
            }
        }

        $restantes = max(0, $total - $usado);

        return [
            'fundadores_total' => $total,
            'fundadores_usado' => $usado,
            'fundadores_restantes' => $restantes,
            'fundadores_activo' => $restantes > 0,
            'fundadores_codigo' => $codigo,
        ];
    }
}