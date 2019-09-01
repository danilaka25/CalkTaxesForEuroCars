<?
require_once("vendor/autoload.php");
require_once("library/simple_html_dom.php");

class Cars
{
	
    //var $price; 
    
    function calc($url)
    {
             
        // $url = 'https://ru.autogidas.lt/skelbimas/bmw-x5-dyzelinas--2002-g-e53-0131302922.html';
        $html = file_get_html($url);         
        
        $price = $html->find('.price', 0);     
        $price = strip_tags($price); // delete html
        $price = preg_replace('~[^0-9]+~', '', $price); // delete all exept numbers       
        
        $year = $html->find('.params-block', 1)->find('.param', 2)->find('.right', 0);     
        $year = strip_tags($year); // delete html
        $year = preg_replace('~[^0-9]+~', '', $year); // delete all exept numbers
        $year = substr($year, 0, 4); //first 4 numbers
        $year = date("Y") - $year; // difference of years
             
        $power = $html->find('.params-block', 1)->find('.param', 3)->find('.right', 0); 
        $power = strip_tags($power);
        $power = substr($power, 0, 3);
              
        $engine = $html->find('.params-block', 1)->find('.param', 4)->find('.right', 0);  
        $engine = strip_tags($engine); // delete html
        $engine = trim($engine);
        
        
        if (($engine == "Дизель") && ($power < 3.5)) {
            
            $rate = 75;
            
        } elseif (($engine == "Дизель") && ($power >= 3.5)) {
            
            $rate = 150;
            
        } elseif (($engine !== "Дизель") && ($power < 3)) {
            
            $rate = 50;
            
        } else {
            
            $rate = 100;
            
        }
          
        global $price_excise;  
        $price_excise = $rate * $power * $year;
        $price_excise = ceil($price_excise); // round     
        return $price_excise;       
    }
    
    function calcprice($url)
    {   
        $html  = file_get_html($url);
        $price = $html->find('.price', 0);
        $price = strip_tags($price); // delete html
        $price = preg_replace('~[^0-9]+~', '', $price); // delete all exept numbers  
        $this->price = $price; 
        return $this->price;       
    }
      
    function calcNds($price)
    {     
        $price_Nds = $price * 0.2;       
        $price_Nds = ceil($price_Nds);      
        return $price_Nds;     
    }
    
    function str_replace_once($search, $replace, $text) // функця убрать первое вхождение m.
    {
        $pos = strpos($text, $search);
        return $pos !== false ? substr_replace($text, $replace, $pos, strlen($search)) : $text;
    }
    
    function checkUrl($url)
    {
        $url = filter_var($url, FILTER_SANITIZE_URL); // Remove all illegal characters from a url   
        $url = $this->str_replace_once('m.', '', $url); // Remove .m mob version
        
        // Validate url
        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            
            // Validate site
            if (mb_stripos($url, "ru.autogidas.lt") !== false) {
                return true;
            } elseif (mb_stripos($url, "ru.autoplius.lt") !== false) {
                return true;
            } else {
                return false;
            }
        }
    }   
}

$token = "735039964:AAG4Q69h0d9Zi6bujCYZXsn8R384CBPkmOs";
$bot   = new \TelegramBot\Api\Client($token);


// cammand start
$bot->command('start', function($message) use ($bot)
{
    $answer = 'Просто надішліть мені посилання на будь-яке авто з цих сайтів і я порахую вартість його розмитнення :)
    ru.autogidas.lt
    ';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// cammand help
$bot->command('help', function($message) use ($bot)
{
    $answer = 'Команды:
	/help - вывод справки';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// cammand hello
$bot->command('hello', function($message) use ($bot)
{
    $text = $message->getText();   
    $param = str_replace('/hello ', '', $text);
    $answer = 'Неизвестная команда';
    if (!empty($param)) {
        $answer = 'Привет, ' . $param;
    }
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// cammand SAY 
$bot->on(function($Update) use ($bot)
{
    
    $message = $Update->getMessage();
    $text    = $message->getText();
    $cid     = $message->getChat()->getId();
       
    if (!empty($text)) {       
        
        $object = new Cars;     
        
        $text = $object->str_replace_once('m.', '', $text);
        
        if ($object->checkUrl($text)) {
                 
            $object->calcprice($text);    
            $answer = "Ціна до розмитнення: " . $object->price . " € 
			НДС: " . $object->calcNds($object->price) . " € 
			Акцизний збір: " . $object->calc($text) . " € 
			Ввізне мито: " . ceil($object->price / 13.69) . " € 
			Пенсійний фонд: " . ceil($object->price / 33.33) . " € 
			Сертифікація ~ 90 €
			Ціна розмитненого авто:
			" . ($object->price + $object->calcNds($object->price) + $object->calc($text) + ceil($object->price / 13.69) + ceil($object->price / 33.33) + 90) . " € 
			Перші 3 місяці на акциз буде діяти знижка 50%
			" . ceil(($object->price + $object->calcNds($object->price) + ($object->calc($text) / 2) + ceil($object->price / 13.69) + ceil($object->price / 33.33) + 90)) . " €";
						                     
        } else {
            $answer = 'НE URL';
        }
           
    }    
		$bot->sendMessage($message->getChat()->getId(), $answer);
    }
    
}, function($message) use ($name)
{
    return true; // if true - команда проходит
});

$bot->run();
?>