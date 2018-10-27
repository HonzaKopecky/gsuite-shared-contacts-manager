<?php

namespace App\Components;

use App\Model\Contact;
use App\Model\ContactService;
use App\Model\SerializableImage;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\FileUpload;
use Tracy\Debugger;

/**
 * Class represents a component that handles upload & send of the profile photo.
 */
class PictureEditor extends Control
{
    /** @var ContactService */
    private $contactService;

    /** @var  Contact */
    private $contact;

    /**
     * PictureEditor constructor.
     * @param ContactService $cs
     * @param Contact|null $contact
     */
    public function __construct(ContactService $cs, Contact $contact = null) {
        parent::__construct();
        $this->contactService = $cs;
        $this->contact = $contact;
    }

    /** Render editor of profile photo
     */
    public function renderEdit() {
        $template = $this->template;
        $template->contact = $this->contact;
        $template->setFile(__DIR__ . '/edit.latte');
        $template->render();
    }

    /**
     * Upload submitted photo to a contact and submit it to Google
     */
    public function handleUpload() {
        /** @var Presenter $presenter */
        $presenter = $this->parent;
        /** @var  FileUpload $file */
        $file = $presenter->request->getFiles()['croppedImage'];
        if(!$file->isImage())
            $presenter->error("File type not supported.");
        $img = $file->toImage();
		$simg = new SerializableImage($img->getImageResource());
		$simg = $simg->scale(100);
        $this->contactService->saveProfilePhoto($this->contact, $simg);
        $this->parent->sendPayload();
    }

    /** Render the profile photo of a contact
     * @param Contact $contact
     */
    public function renderView(Contact $contact) {
        $template = $this->template;
        $template->contact = $contact;
        $template->setFile(__DIR__ . '/view.latte');
        $template->render();
    }
}