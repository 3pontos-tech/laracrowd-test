<?php

namespace Platform\Banking\Enums\Wallet;

use App\Enums\Concerns\StringifyEnum;

enum WalletStatus: string
{
    use StringifyEnum;

    case Inactive = 'inactive';

    case Active = 'active';

    case EndOfLife = 'end-of-life';

    case Blocked = 'blocked';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Saldo já liquidado, isto é, dinheiro que de fato é do investidor.
     *
     * Enquanto o aporte está em contrato, pagamento (Inactive) ou bloqueado, a
     * carteira já pode carregar o crédito do aporte inicial pelo valor cheio sem
     * que esse dinheiro esteja disponível. Todo bloco da Home que expõe valor —
     * card de saldo, série de evolução, histórico de rentabilidade e tabela de
     * detalhes — usa este recorte, para que os mesmos R$ 100 mil não fiquem
     * escondidos como "pendente" em um lugar e rendendo em outro.
     */
    public function isSettled(): bool
    {
        return match ($this) {
            self::Active, self::EndOfLife => true,
            self::Inactive, self::Blocked => false,
        };
    }
}
