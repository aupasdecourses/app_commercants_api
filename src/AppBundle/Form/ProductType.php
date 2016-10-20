<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku')
            ->add('ref')
            ->add('name')
            ->add('available')
            ->add('selected')
            ->add('price')
            ->add('priceUnit')
            ->add('shortDescription')
            ->add('portionWeight')
            ->add('portionNumber')
            ->add('tax')
            ->add('origin')
            ->add('bio')
            ->add('user')
        ;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
            ]
        );
    }
}
