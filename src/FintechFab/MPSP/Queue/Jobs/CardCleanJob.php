<?php namespace FintechFab\MPSP\Queue\Jobs;

use Log;
use FintechFab\MPSP\Entities\Card;

class CardCleanJob extends AbstractJob
{
	public function run($data)
	{
		$cardId = $data['card_id'];

		$transferCard = Card::find($cardId);
		if (!$transferCard) {
			Log::error("CardCleanJob::run() Запись transfer_cards не найдена", $data);

			return;
		}

		$transferCard->clean();
	}

} 