<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$origin = sms_api_origin();
$sendUrl = ($origin !== '' ? $origin : '') . '/api/sms/send';
$creditUrl = ($origin !== '' ? $origin : '') . '/api/sms/credit';
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">ग्राहक मार्गदर्शन</h1>
    <p class="text-slate-500 text-sm">खाता, सेवा, SMS, र API यही पोर्टलबाट चल्छ। हरेक बटनको नेपाली अर्थ तल छ।</p>
</div>
<nav class="flex flex-wrap gap-2 mb-8" aria-label="खण्ड">
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#account">खाता</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#wallet">पैसा</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#kyc">पहिचान</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#sms">SMS</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#api">API</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#voice">आवाज</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#domain">डोमेन</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#services">सेवा</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#support">सहयोग</a>
</nav>

<section id="account" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">१. खाता खोल्ने र साइन इन</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Client portal मा Register गरेर नाम, इमेल, १० अंकको मोबाइल र कम्तीमा ८ अक्षरको पासवर्ड राख्नुहोस्। उही इमेल, मोबाइल, वा कम्पनी नामले अर्को खाता बन्दैन। कम्पनी खाली छाड्न मिल्छ। खाता बनेपछि Aakash Tech बाट मेल आउँछ। पासवर्ड त्यो मेलमा हुँदैन।</li>
        <li>साइन इन पछि Google Authenticator जोड्नुहोस्। अर्को पटक इमेल, पासवर्ड, र ६ अंकको कोड चाहिन्छ। साइन इन पूरा हुँदा इमेलमा सुरक्षा सूचना आउँछ। SMS क्रेडिट काटिँदैन।</li>
        <li>पासवर्ड बिर्सिए Forgot password थिच्नुहोस्। मेलमा ३० मिनेटको लिंक आउँछ। लिंक एक पटक मात्र चल्छ।</li>
        <li>नयाँ पासवर्ड सेभ भएपछि फेरि साइन इन गर्नुहोस्। Authenticator अझै चाहिन्छ।</li>
        <li>प्रोफाइलबाट नाम, कम्पनी, ठेगाना र पासवर्ड बदल्न सकिन्छ। साइन इन इमेल र मोबाइल आफैँ बदल्न मिल्दैन। बदल्नुपरे Support बाट टोलीलाई लेख्नुहोस्।</li>
    </ol>
</section>

<section id="wallet" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">२. पैसा र सेवा किन्ने</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Wallet मा देखाइएको eSewa, Khalti, वा बैंकबाट रकम पठाउनुहोस्।</li>
        <li>रकम, विधि, र कारोबार कोड हालेर Submit top-up थिच्नुहोस्। टोलीले पुष्टि गरेपछि वालेटमा रकम आउँछ।</li>
        <li>Buy Services बाट SMS, आवाज, होस्टिङ, इमेल, वेबसाइट, वा तालिम छान्नुहोस्। बिलमा १३ प्रतिशत भ्याट जोडिएको हुन्छ।</li>
        <li>वालेटले नपुगे पहिले रकम थप्नुहोस्, अनि त्यही अर्डर फेरि पठाउनुहोस्।</li>
        <li>वार्षिक वा मासिक सेवाको नवीकरण वालेटबाट आफैँ काटिन्छ। रकम नपुगे ड्यासबोर्डमा सूचना आउँछ र मेल पनि जान्छ। रकम नथपेपछि सेवा रोकिन्छ, वालेट पुगेपछि फेरि चल्छ।</li>
    </ol>
</section>

<section id="kyc" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">३. पहिचान</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>Identity मा व्यक्ति वा संस्थाको विवरण र कागज पठाउनुहोस्। स्वीकृत नभएसम्म SMS, आवाज काम, र API टोकन बन्द रहन्छ। क्रेडिट भने पहिले किन्न सकिन्छ।</p>
        <p>फिर्ता आएमा कारण पढेर कागज अपडेट गर्नुहोस्। स्वीकृत भएपछि SMS ड्यासबोर्ड खुल्छ।</p>
    </div>
</section>

<section id="sms" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">४. SMS पठाउने</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Send SMS खोल्नुहोस्। क्रेडिट नभए पहिले Buy SMS credit गर्नुहोस्।</li>
        <li>सन्देश लेख्नुहोस्। अंग्रेजी १६० अक्षरमा १ क्रेडिट लाग्छ। नेपाली युनिकोड ७० अक्षरमा १ क्रेडिट लाग्छ। लामो सन्देशमा भाग बढ्छ।</li>
        <li>नम्बर ९७ वा ९८ बाट सुरु हुने १० अंकको हुनुपर्छ। एक पटकमा बढीमा ५०० नम्बर।</li>
        <li>सन्देशमा <code class="text-brand-300">{name}</code> लेखे र हरेक लाइनमा <code class="text-brand-300">राम, 9800000001</code> राखे त्यो व्यक्तिको नाम जान्छ। नाम नभएको लाइनमा खाली नाम हट्छ।</li>
        <li>घोषणा स्वीकार गरेर पठाउनुहोस्। पठाइएको सन्देशको क्रेडिट काटिन्छ।</li>
        <li>नेपाल कानुनले नमिल्ने ढाँचा रोकिन्छ र क्रेडिट काटिँदैन। बैठक, चाड, र आफ्नै OTP जस्तो सूचना जान्छ।</li>
        <li>पछि पठाउन समय राख्न सकिन्छ। समय नेपालको हो। रद्द गरे क्रेडिट लाग्दैन।</li>
        <li>SMS logs मा पठाइएको, असफल, र बाँकी हेर्नुहोस्। Send again ले त्यही पाठ फेरि भर्छ।</li>
        <li>क्रेडिट १०० भन्दा कम हुँदा ड्यासबोर्ड र SMS पृष्ठमा किन्नु भन्ने सूचना आउँछ। टोलीले थपेको वा वालेटबाट किनेको क्रेडिट Credits added मा देखिन्छ।</li>
    </ol>
</section>

<section id="api" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">५. API सेटअप</h2></div>
    <div class="p-5 space-y-3 text-sm text-slate-300">
        <p>वेबसाइट वा एपबाट SMS पठाउन SMS API मा टोकन बनाउनुहोस्। पहिचान स्वीकृत भएको हुनुपर्छ।</p>
        <ol class="list-decimal pl-5 space-y-2">
            <li>टोकनको नाम राख्नुहोस्, जस्तै Website OTP।</li>
            <li>सर्भरको IP राख्न चाहनुहुन्छ भने लेख्नुहोस्। खाली छाडे कुनै ठेगानाबाट चल्छ।</li>
            <li>घोषणामा ठीक चिन्ह लगाएर Create token थिच्नुहोस्।</li>
            <li>देखिएको टोकन तुरुन्त कपी गर्नुहोस्। फेरि देखिँदैन। सक्रिय टोकन बढीमा ५ वटा हुन्छ।</li>
            <li>टोकन चाहिँदैन भने Revoke थिच्नुहोस्। त्यो टोकनको कल बन्द हुन्छ।</li>
        </ol>
        <p>पठाउने ठेगाना POST हो। फारम वा JSON दुवै चल्छ। एक मिनेटमा ३० पटकसम्म।</p>
        <p class="break-all text-slate-400"><?= e($sendUrl) ?></p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">curl -X POST <?= e($sendUrl) ?> \
  -d auth_token=तपाईंको_टोकन \
  -d to=98XXXXXXXX \
  -d text='तपाईंको कोड ४४९१२० हो।'</pre>
        <ul class="list-disc pl-5 space-y-1">
            <li><code class="text-brand-300">auth_token</code> — अहिले कपी गरेको टोकन। हेडरमा Bearer ले पनि पठाउन सकिन्छ।</li>
            <li><code class="text-brand-300">to</code> — एउटा नम्बर, वा अल्पविरामले छुट्याएका नम्बर। बढीमा ५००।</li>
            <li><code class="text-brand-300">text</code> — पठाउने सन्देश।</li>
        </ul>
        <p>सफल जवाफमा <code class="text-brand-300">error: false</code>, पठाइएको गन्ती, लागेको क्रेडिट, र बाँकी क्रेडिट आउँछ।</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>४०१ — टोकन मिलेन।</li>
            <li>४०० — सन्देश स्वीकार भएन। ती नम्बरको क्रेडिट फर्किन्छ। कानुनले नमिल्ने पाठ पनि ४०० हो र काटिँदैन।</li>
            <li>४२९ — एक मिनेटमा ३० पटकभन्दा बढी भयो।</li>
            <li>४०५ — POST बाहेकको विधि।</li>
        </ul>
        <p class="break-all text-slate-400">बाँकी क्रेडिट: POST <?= e($creditUrl) ?> मा उही <code class="text-brand-300">auth_token</code> पठाउनुहोस्।</p>
    </div>
</section>

<section id="voice" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">६. आवाज कल</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>Messages मा आवाज काम सेभ गर्नुहोस्। पहिचान स्वीकृत चाहिन्छ। साइटले कल आफैँ लगाउँदैन। टोलीले कल लगाएर Placed चिन्ह लगाएपछि क्रेडिट काटिन्छ।</p>
    </div>
</section>

<section id="domain" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">७. डोमेन र WHOIS</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Domain registration मा नाम र अन्त्य छानेर उपलब्धता हेर्नुहोस्। .np मा कागज चाहिन्छ।</li>
        <li>खाली नामको अनुरोध पठाउनुहोस्। भुक्तानी त्यही बेला काटिँदैन।</li>
        <li>My domains मा वार्षिक बिल वालेटबाट तिर्नुहोस्। टोलीले दर्ता गरेपछि स्थिति Active हुन्छ।</li>
        <li>WHOIS check up मा कसको नाममा छ भन्ने सार्वजनिक रेकर्ड हेर्नुहोस्। रेकर्ड नभएको नाम दर्तामा लैजान सकिन्छ।</li>
    </ol>
</section>

<section id="services" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">८. होस्टिङ, इमेल, वेबसाइट र तालिम</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>होस्टिङ किनेपछि टोलीले cPanel तयार पार्छ। My Services मा cPanel login आएपछि त्यो थिच्नुहोस्। प्यानल तपाईंको ब्राउजरमा खुल्छ।</p>
        <p>इमेलमा टोलीले मेलबक्सको नाम र पासवर्ड पठाउँछ। नाम जोडिएपछि Open email थिच्नुहोस्। इनबक्स mail.तपाईंको-नाम मा खुल्छ।</p>
        <p>वेबसाइट तयार भएपछि Open website आउँछ। तालिमको मिति टोलीले पुष्टि गरेपछि My Services मा देखिन्छ।</p>
    </div>
</section>

<section id="support" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">९. सहयोग</h2></div>
    <div class="p-5 text-sm text-slate-300">
        <p>Support मा विषय र विवरण लेखेर टिकट खोल्नुहोस्। जवाफ यहीँ आउँछ। फोन वा इमेल साइटको सम्पर्कमा पनि छ।</p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
