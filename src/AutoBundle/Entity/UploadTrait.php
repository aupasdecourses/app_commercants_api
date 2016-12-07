<?php
namespace AutoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use PHPImageWorkshop\ImageWorkshop;

trait UploadTrait
{
    public function getAbsolutePath($name)
    {
        return empty($this->$name) ? null : $this->getUploadRootDir().'/'.$this->$name;
    }

    protected function getUploadRootDir()
    {
        return __DIR__.$this->config['upload']['rootDir'].$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        return $this->config['upload']['uploadDir'].$this->getId();
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        foreach ($this->uploadFiles as $name) {
            $nameFile = $name.'File';

            if (null !== $this->$nameFile) {
                $this->doUpload($name);
            }
        }
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        foreach ($this->uploadFiles as $name) {
            $nameFile = $name.'File';

            if (null !== $this->$nameFile) {
                $tempName = 'temp'.ucfirst($name);

                $this->removeUpload($tempName);

                if (property_exists($this, 'name')) {
                    $slug = $this->createSlug($this->name);
                } else {
                    $slug = $name;
                }

                $this->$name = $slug.'-'.substr(sha1(uniqid(mt_rand(), true)), 0, 3).'-'.$this->getId(
                    ).'.'.$this->$nameFile->guessExtension();
            }
        }
    }

    /**
     * @param string $name
     *
     * @ORM\PostRemove()
     */
    public function removeUpload($name = null)
    {
        if (isset($name)
            && is_string($name)
        ) {
            if ($file = $this->getAbsolutePath($name)) {
                if (is_writable($file)) {
                    unlink($file);
                }

                $realName = substr($name, 0, 4) == 'temp' ? strtolower(substr($name, 4)) : $name;

                if (isset($this->config['upload'][$realName]['thumbnail'])
                    && ($thumb = $this->getUploadRootDir().'/thumbs/'.$this->$name)
                    && is_writable($thumb)
                ) {
                    unlink($thumb);
                }
            }
        } else {
            foreach ($this->uploadFiles as $file) {
                $this->removeUpload($file);
            }
        }
    }

    /**
     * @param string $name
     */
    public function doUpload($name)
    {
        $config    = $this->config['upload'][$name];
        $nameFile  = $name.'File';
        $uploadDir = $this->getUploadRootDir();

        $this->$nameFile->move($uploadDir, $this->$name);

        $newdir = true;

        if (is_dir($uploadDir)) {
            $newdir = false;
        }

        $resized = ImageWorkshop::initFromPath($this->getUploadRootDir().'/'.$this->$name);

        if (isset($config['thumbnail'])) {
            $thumbnail = clone $resized;

            if ($thumbnail->getWidth() != $config['thumbnail']['width']) {
                if ($thumbnail->getWidth() > $config['thumbnail']['width']) {
                    $thumbnail->resizeInPixel(
                        $config['thumbnail']['width'],
                        $config['thumbnail']['height'],
                        true,
                        0,
                        0,
                        'MM'
                    );
                } else {
                    $canvas = ImageWorkshop::initVirginLayer(
                        $config['thumbnail']['width'],
                        $config['thumbnail']['height']
                    );
                    $canvas->addLayerOnTop($thumbnail, 0, 0, 'MM');

                    $thumbnail = $canvas;
                }
            }

            $thumbnail->save($uploadDir.'/thumbs', $this->$name, true, null, '80');
        }

        if (isset($config['resize'])
            && $resized->getWidth() >= $config['resize']['width']
        ) {
            $resized->resizeInPixel($config['resize']['width'], $config['resize']['height'], true, 0, 0, 'MM');
        }

        $resized->save($uploadDir, $this->$name, true, null, '90');

        if ($newdir
            && is_dir($uploadDir)
        ) {
            chmod($uploadDir, 0777);

            if (isset($thumbnail)) {
                chmod($uploadDir.'/thumbs', 0777);
            }
        }

        unset($this->$nameFile);
    }

    protected function createSlug($name)
    {
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", "-", $clean);

        return $clean;
    }
}
