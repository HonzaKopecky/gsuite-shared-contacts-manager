<?php

namespace App\Model;

use App\Components\PictureEditor;
use Nextras\Datagrid\Datagrid;

/** Extended version of Nextras\Datagrid that support custom component to be used inside the table
 */
class MyDatagrid extends Datagrid {
    /** @var ContactService */
    private $cs;

    public function __construct(ContactService $cs)
    {
        parent::__construct();
        $this->cs = $cs;
    }

    /** Create picture editor component
     * @return PictureEditor
     */
    protected function createComponentPictureEditor()
    {
        return new PictureEditor($this->cs);
    }
}