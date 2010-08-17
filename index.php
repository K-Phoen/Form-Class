<?php
error_reporting(E_ALL);//pour le dev

require_once 'Form.class.php';

function test($value)
{
    if($value == 'yooo')
        return '';
    else
        return 'Doit être égal à « yooo »';
}

//création du formulaire
$form = new Form();
$form->add('Text', 'pseudo')->setLabel('Nom')->setErrorText('required', 'Je veux savoir ton nom !')->addValidationRule('test');
$form->add('Email', 'mail')->setLabel('Adresse Mail');
$form->add('Text', 'sujet')->setLabel('Sujet');
$form->add('Textarea', 'message')->setLabel('Message')->cols('54%')->rows(7)->setMinLength(10);
$form->add('SubmitButton', 'Envoyer')->addClass('button');


/**
*
*   Traitement du formulaire de contact
*
**/
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $form->bound($_POST);
    
    if($form->isValid())
    {
        echo 'valid form<br />';
        var_dump($_POST);
    }
    else //affichage des erreurs
    {
        echo 'erreur !<br />';
        echo '<pre>';
        var_dump($form->getErrors());
        var_dump($_POST);
        echo '</pre>';
    }
}
?>
<html>
<head>
    <title>Page de test</title>
</head>
<body>
<h1>Test</h1>
<p>
    <?php echo $form; ?>
</p>
</body>
</html>
