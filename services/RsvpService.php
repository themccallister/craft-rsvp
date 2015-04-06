<?php namespace Craft;

class RsvpService extends BaseApplicationComponent {

	/**
	 * @var
	 */
	protected $rsvpRecord;
	/**
	 * @var
	 */
	protected $_rsvpById;
	/**
	 * @var
	 */
	protected $rsvp;

	/**
	 * @param null $rsvpRecord
	 */
	public function init($rsvpRecord = null)
	{
		$this->rsvpRecord = $rsvpRecord;

		if (is_null($this->rsvpRecord)) {
			$this->rsvpRecord = RsvpRecord::model();
		}
	}

	/**
	 * Get all RSVPS.
	 *
	 * @param null $indexBy
	 * @return mixed
	 */
	public function getAllRsvps($indexBy = null)
	{
		$rsvps = RsvpRecord::model()->findAll();

		return $rsvps;
	}

	/**
	 * Get an RSVP by ID.
	 *
	 * @param $rsvpId
	 * @return mixed
	 */
	public function getRsvpById($rsvpId)
	{
		if (!isset($this->_rsvpById) || !array_key_exists($irsvpIdd, $this->_rsvpById)) {
			$rsvpRecord = RsvpRecord::model()->findById($rsvpId);

			if ($rsvpRecord) {
				$this->_rsvpById[$rsvpId] = RsvpModel::populateModel($rsvpRecord);
			}
			else {
				$this->_rsvpById[$rsvpId] = null;
			}
		}

		return $this->_rsvpById[$rsvpId];
	}

	/**
	 * Save an RSVP.
	 *
	 * @param RsvpModel $model
	 * @return bool
	 * @throws \Exception
	 */
	public function saveRsvp(RsvpModel $model)
	{
		$record = new RsvpRecord();

		$record->name = $model->name;
		$record->email = $model->email;
		$record->phone = $model->phone;
		$record->attending = $model->attending;
		$record->guests = $model->guests;
		$record->comments = $model->comments;

		$record->validate();

		$model->addErrors($record->getErrors());

		if ($model->hasErrors()) {
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try {
			$record->save(false);

			if ($transaction !== null) {
				$transaction->commit();
			}

			else {
				return false;
			}
		} catch (\Exception $e) {
			if ($transaction !== null) {
				$transaction->rollback();
			}

			throw $e;
		}

		$settings = craft()->plugins->getPlugin('rsvp')->getSettings();

		// fire off email notification
		if (!empty($settings->notificationEmail)) {
			$email = new EmailModel();
			$email->toEmail = $settings->notificationEmail;
			$email->subject = $settings->notificationSubject;
			$email->body = $settings->notificationMessage;

			craft()->email->sendEmail($email);
		}

		return true;
	}

	/**
	 * @param Event $event
	 */
	public function onSave(Event $event)
	{
		$this->raiseEvent('onSave', $event);
	}

}
