<?php

use Wikimedia\IPUtils;

class SpecialRemoveGlobalBlock extends FormSpecialPage {
	/** @var string|null */
	private $ip;

	/** @var int|null */
	private $id;

	public function __construct() {
		parent::__construct( 'RemoveGlobalBlock', 'globalblock' );
	}

	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'globalblocking-unblock' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->enableClientCache( false );
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmit( array $data ) {
		$this->ip = trim( $data['ipaddress'] );

		if ( !IPUtils::isIPAddress( $this->ip ) ) {
			return Status::newFatal( $this->msg( 'globalblocking-unblock-ipinvalid', $this->ip ) );
		}

		$this->id = GlobalBlocking::getGlobalBlockId( $this->ip );
		if ( $this->id === 0 ) {
			return Status::newFatal( $this->msg( 'globalblocking-notblocked', $this->ip ) );
		}

		$dbw = GlobalBlocking::getGlobalBlockingDatabase( DB_MASTER );
		$dbw->delete( 'globalblocks', [ 'gb_id' => $this->id ], __METHOD__ );

		$page = new LogPage( 'gblblock' );
		$page->addEntry(
			'gunblock',
			Title::makeTitleSafe( NS_USER, $this->ip ),
			$data['reason'],
			[],
			$this->getUser()
		);

		return Status::newGood();
	}

	public function onSuccess() {
		$msg = $this->msg( 'globalblocking-unblock-unblocked', $this->ip, $this->id )->parseAsBlock();
		$link = $this->getLinkRenderer()->makeKnownLink(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$this->msg( 'globalblocking-return' )->text()
		);

		$this->getOutput()->addHTML( $msg . $link );
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setWrapperLegendMsg( 'globalblocking-unblock-legend' );
		$form->setSubmitTextMsg( 'globalblocking-unblock-submit' );
		$form->setPreText( $this->msg( 'globalblocking-unblock-intro' )->parse() );
	}

	protected function getFormFields() {
		return [
			'ipaddress' => [
				'name' => 'address',
				'type' => 'text',
				'id' => 'mw-globalblocking-ipaddress',
				'label-message' => 'globalblocking-ipaddress',
				'required' => true,
				'default' => $this->par,
			],
			'reason' => [
				'name' => 'wpReason',
				'type' => 'text',
				'id' => 'mw-globalblocking-unblock-reason',
				'label-message' => 'globalblocking-unblock-reason',
			],
		];
	}

	public function doesWrites() {
		return true;
	}

	protected function getGroupName() {
		return 'users';
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}
}
