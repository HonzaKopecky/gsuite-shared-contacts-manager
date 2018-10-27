<?php

namespace App\Model;

use Nette\Utils\Image;

/** Extended version of Nette\Utils\Image that support serialization
 */
class SerializableImage extends Image implements \Serializable {
	public function serialize()
	{
		return \serialize(['data' => (string)$this]);
	}

	public function unserialize($serialized)
	{
		$unserializedArray = \unserialize($serialized);
		$this->setImageResource((SerializableImage::fromString($unserializedArray['data']))->getImageResource());
	}
}