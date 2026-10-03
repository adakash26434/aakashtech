<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">प्रशासक मार्गदर्शन</h1>
    <p class="text-slate-500 text-sm">यो पृष्ठ आकाश टेक्नोलोजिजको प्रशासक पोर्टल कसरी चलाउने भन्ने हो। ग्राहकले विक्रेताको नाम देख्दैन। त्यो विवरण यहीँ रहन्छ।</p>
</div>
<nav class="flex flex-wrap gap-2 mb-8" aria-label="खण्ड">
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#sign-in">साइन इन</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#dashboard">ड्यासबोर्ड</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#kyc">पहिचान</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#clients">ग्राहक</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#billing">बिलिङ</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#domain">डोमेन</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#hosting">होस्टिङ</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#email">इमेल</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#delivery">वेबसाइट</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#sms-line">SMS लाइन</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#voice">आवाज</a>
    <a class="px-3 py-2 rounded-full border border-slate-700 text-sm text-slate-300" href="#settings">सेटिङ</a>
</nav>

<section id="sign-in" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">१. साइन इन</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Admin login मा इमेल र पासवर्ड हाल्नुहोस्। नयाँ पासवर्ड कम्तीमा ८ अक्षरको हुन्छ।</li>
        <li>पहिलो पटक Google Authenticator मा खाता जोड्नुहोस्। पछि हरेक साइन इनमा ६ अंकको कोड चाहिन्छ।</li>
        <li>ब्याकअप कोड एक पटक मात्र देखिन्छ। फोन नभएको बेला त्यो कोडले साइन इन हुन्छ, र एक पटक प्रयोग भएपछि सकिन्छ।</li>
        <li>पासवर्ड बदल्न Settings मा जानुहोस्। बदलेपछि अर्को साइन इन भएको सत्र बन्द हुन्छ।</li>
    </ol>
</section>

<section id="dashboard" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">२. ड्यासबोर्डमा के हेर्ने</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>माथिको सूचना त्यही काम देखाउँछ जुन तपाईंले सक्नु बाँकी छ।</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>वालेट टप-अप पुष्टि — Billing</li>
            <li>वेबसाइट वा तालिम बुकिङ — Billing</li>
            <li>cPanel लगइन नभएको होस्टिङ — Billing</li>
            <li>ग्राहकलाई देखाउन बाँकी इमेल लगइन — Billing</li>
            <li>नयाँ सोधपुछ — Inquiries</li>
            <li>खुला समर्थन टिकट — Support Tickets</li>
            <li>भुक्तानी भएको डोमेन — Domains</li>
            <li>पर्खाइमा रहेको आवाज काम — Messages</li>
            <li>पर्खाइमा रहेको पहिचान — Identity</li>
        </ul>
    </div>
</section>

<section id="kyc" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">३. पहिचान स्वीकृत</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Identity मा पर्खाइमा रहेको निवेदन खोल्नुहोस्।</li>
        <li>नागरिकता, दर्ता, प्यान वा अधिकार प्राप्त व्यक्तिको कागज हेर्नुहोस्।</li>
        <li>मिल्छ भने Approve गर्नुहोस्। नमिल्छ भने कारण लेखेर फिर्ता पठाउनुहोस्।</li>
        <li>स्वीकृत नभएसम्म ग्राहकले SMS पठाउन, आवाज काम सेभ गर्न, र API टोकन बनाउन पाउँदैन।</li>
    </ol>
</section>

<section id="clients" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">४. ग्राहक, सोधपुछ र टिकट</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>Clients मा आफैँ खाता बनाउन Create an account प्रयोग गर्नुहोस्। वेबसाइटबाट दर्ता नभएको ग्राहकलाई नाम, इमेल, मोबाइल र पासवर्ड दिनुहोस्। उही इमेल, मोबाइल, वा कम्पनी नामले अर्को खाता बन्दैन। कम्पनी खाली छाड्न मिल्छ। उनी पहिलो साइन इनमा Google Authenticator जोड्छन्।</p>
        <p>पैसा कार्यालयमै लिइसकेको सेवा Add a paid service बाट थप्नुहोस्। वालेट काटिँदैन। SMS वा आवाजमा संख्या लेख्नुहोस्। वार्षिक सेवा पछि वालेटबाट नवीकरण हुन्छ।</p>
        <p>Clients मा नाम वा इमेल खोज्नुहोस्। खाता रोक्न Suspend थिच्नुहोस्। रोकिएको खाताले साइन इन गर्न र पासवर्ड रिसेट मेल पाउन सक्दैन।</p>
        <p>साइन इन इमेल र मोबाइल ग्राहकले बदल्न सक्दैन। मागेपछि Change email or mobile बाट दुवै अनिवार्य राखेर सेभ गर्नुहोस्। पुरानो इमेललाई खबर जान्छ। स्वीकृत परिचय पनि ग्राहकले सच्याउन सक्दैन।</p>
        <p>Inquiries मा वेबसाइटको सम्पर्क फारम आउँछ। पढेपछि स्थिति बदल्नुहोस्।</p>
        <p>Support Tickets मा जवाफ लेखेर स्थिति Open, In progress, वा Resolved राख्नुहोस्।</p>
    </div>
</section>

<section id="billing" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">५. वालेट र बिलिङ</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Settings मा eSewa, Khalti, वा बैंक विवरण सेभ गर्नुहोस्। खाली रहेको विधि ग्राहकलाई देखिँदैन।</li>
        <li>ग्राहकले वालेटमा रकम र कारोबार कोड पठाउँछ। Billing मा त्यो टप-अप पुष्टि गर्नुहोस्। पुष्टि पछि रकम वालेटमा जान्छ।</li>
        <li>नगद वा फर्मबाहिरको भुक्तानी Add a payment yourself बाट हाल्नुहोस्। ग्राहक, रकम, र कहाँ लियो लेख्नुहोस्। रकम तुरुन्त वालेटमा जान्छ र Payments you added मा देखिन्छ।</li>
        <li>सेवाको मूल्य Billing मा रहन्छ। सार्वजनिक पृष्ठ र ग्राहकको पसलले त्यही मूल्य देखाउँछ। बिलमा १३ प्रतिशत भ्याट जोडिन्छ।</li>
        <li>होस्टिङ, डोमेन र त्यस्तै नवीकरण वालेटबाट आफैँ काटिन्छ। रकम नपुगे अर्को पटक फेरि प्रयास हुन्छ, अनि सेवा रोकिन सक्छ।</li>
    </ol>
</section>

<section id="domain" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">६. डोमेन</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>ग्राहकले नाम जाँचेर अनुरोध पठाउँछ र वार्षिक बिल वालेटबाट तिर्छ।</li>
        <li>Domains मा भुक्तानी भएको नाम खोल्नुहोस्। .np नाम <a class="text-brand-400" href="https://register.com.np/" target="_blank" rel="noopener">register.com.np</a> मा धारक, ठेगाना र कागजसहित दर्ता गर्नुहोस्।</li>
        <li>कागज JPG वा PNG र करिब ८०० KB भित्र भए त्यहीँ अपलोड गर्न सजिलो हुन्छ।</li>
        <li>दर्ता सकिएपछि स्थिति Active गर्नुहोस्। त्यसपछि वर्ष सुरु हुन्छ र नवीकरण वालेटबाट हुन्छ।</li>
        <li>कार्यालयमै दर्ता भइसकेको नाम Domains मा Add a name you already registered बाट हाल्नुहोस्। अहिले वालेट काटिँदैन। वर्ष पछि वालेटबाट नवीकरण हुन्छ।</li>
        <li>१४ दिनभित्र नवीकरण आउने होस्टिङ, डोमेन, वा इमेल ड्यासबोर्डमा देखिन्छ।</li>
        <li>.edu.np, .gov.np र .mil.np को हकदार टोलीले पुष्टि गर्छ।</li>
    </ol>
</section>

<section id="hosting" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">७. होस्टिङ र cPanel</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>ग्राहकले होस्टिङ किनेपछि सेवा Active हुन्छ। cPanel अझै देखिँदैन।</li>
        <li>Billing को Hosting logins मा प्रयोगकर्ता नाम र पासवर्ड राख्नुहोस्। नाम अक्षरबाट सुरु हुन्छ र १६ अक्षरसम्म हुन्छ।</li>
        <li>लगइन ठेगाना खाली छाड्नुहोस्। ग्राहकको ब्राउजर आफ्नै डोमेनको cPanel मा जान्छ।</li>
        <li>प्रदायकको होस्टनाम भर्नुभयो भने लगइन पछि ठेगाना पट्टीमा त्यो नाम देखिन सक्छ।</li>
        <li>पासवर्ड डाटाबेसमा सिल भएर बस्छ र ग्राहकले cPanel खोल्दा एक पटक मात्र पठिन्छ।</li>
    </ol>
</section>

<section id="email" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">८. डोमेन इमेल</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>Zoho मा मेलबक्स बनाउनुहोस्। कस्टम लगइन mail.ग्राहकको-डोमेन राख्नुहोस् र त्यो नाम Zoho तर्फ देखाउनुहोस्।</li>
        <li>Billing को Email logins मा डोमेन र मेलबक्स नाम लेख्नुहोस्, जस्तै info र sales। योजनाले दिएजति मात्र। ठेगाना खाली छाड्नुहोस्।</li>
        <li>Zoho को ठेगाना सेभ हुँदैन। ग्राहकले mail.आफ्नो-नाम खोल्छ र तपाईंले पठाएको पासवर्डले info@त्यो-नाम मा साइन इन गर्छ। पासवर्ड साइटमा राखिँदैन।</li>
        <li>साइटले इमेलको पासवर्ड राख्दैन र आफैँ पठाउँदैन।</li>
    </ol>
</section>

<section id="delivery" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">९. वेबसाइट र तालिम</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>वेबसाइट बुक हुन्छ। तयार भएपछि Billing मा https ठेगाना र छोटो नोट सेभ गर्नुहोस्। स्थिति Active हुन्छ। ग्राहकले साइट खोल्छ र नोट देख्छ।</p>
        <p>तालिम Booked नै रहन्छ। मिति र स्थान राखेर Confirmed वा Done चिन्ह लगाउनुहोस्। ग्राहकले मिति र स्थान देख्छ।</p>
    </div>
</section>

<section id="sms-line" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">१०. SMS लाइन र ग्राहकको API</h2></div>
    <div class="p-5 space-y-3 text-sm text-slate-300">
        <p>थोक SMS विक्रेतासँग किनेको API key यही पोर्टलको <a class="text-brand-400" href="sms-line.php">SMS line</a> मा टाँस्नुहोस्। फाइल खोल्न पर्दैन। ग्राहकले त्यो key देख्दैन।</p>
        <ol class="list-decimal pl-5 space-y-2">
            <li>Aakash SMS को प्यानलबाट <strong class="text-white">auth token</strong> कपी गर्नुहोस्। त्यो v4 लाइनको हेडरमा जान्छ। Sparrow को प्यानलबाट <strong class="text-white">token</strong> कपी गर्नुहोस्। दुवै यही फर्मको API key मा जान्छ।</li>
            <li>SMS line खोल्नुहोस्। Where the bulk SMS is bought मा त्यो खाता छान्नुहोस्।</li>
            <li>API key मा टाँस्नुहोस्। टाँस्दा हेर्न Show the key while pasting खोल्नुहोस्। Save line पछि key लुक्छ र अन्तिम ४ अक्षर मात्र देखिन्छ।</li>
            <li>Aakash SMS छाने Sender name हाल्नु पर्दैन। v4 ले त्यो माग्दैन। फोनमा देखिने नाम त्यो टोकनमा दर्ता भएको नाम हो।</li>
            <li>Sparrow छाने मात्र Sender name हाल्नुहोस्। ३ देखि ११ अक्षर। ग्राहकको आफ्नै नाम चलाउनु छ भने Approved names छान्नुहोस्।</li>
            <li>Send URL खाली छाड्नुहोस्। Aakash SMS मा <strong class="text-white">sms/v4/send-user</strong> आफैँ लाग्छ। बाँकी क्रेडिट <strong class="text-white">sms/v4/credit</strong> बाट आउँछ।</li>
            <li>Save line थिच्नुहोस्। हरियोमा Connected र key को अन्तिम ४ अक्षर आएपछि जोडियो।</li>
            <li>Check line balance ले विक्रेताको बाँकी क्रेडिट देखाउँछ। त्यो ग्राहकको क्रेडिट होइन।</li>
            <li>Send a check ले एउटा नेपाली मोबाइलमा जाँच सन्देश पठाउँछ। त्यसले ग्राहकको क्रेडिट काट्दैन। विक्रेताको क्रेडिट लाग्छ।</li>
            <li>Key बदल्न नयाँ key टाँसेर फेरि सेभ गर्नुहोस्। खाली छाडे पुरानै रहन्छ। Not connected छानेर सेभ गरे जोडाइ हटाउँछ।</li>
            <li>Bulk line left भनेको विक्रेतासँग तपाईंको थोक बाँकी हो। पृष्ठ खोल्दा आफैँ जाँचिन्छ। Still with clients भनेको ग्राहकलाई दिइसकेको बाँकी हो। थोक बाँकी त्योभन्दा कम भए ड्यासबोर्डमा किन्नु भन्ने सूचना आउँछ।</li>
            <li>ग्राहक पोर्टलमा यो API key फेरि हाल्नु पर्दैन। ड्यासबोर्ड र ग्राहकको आफ्नै टोकन दुवै यही लाइनबाट जान्छ।</li>
            <li>Who received credits मा कसले कति पायो देखिन्छ: वालेटबाट किनेको, नवीकरण, वा तपाईंले थपेको।</li>
            <li>कार्यालयमै पैसा लिएर क्रेडिट दिन Add SMS credits प्रयोग गर्नुहोस्। ग्राहक, संख्या, र कारण लेख्नुहोस्।</li>
            <li>Sent message history मा ग्राहक, नम्बर, वा सन्देशको शब्द खोज्नुहोस्। पूरा पाठ खुल्छ, ताकि गलत सन्देश गयो कि हेर्न सकिन्छ।</li>
        </ol>
        <p>ग्राहकको API यो साइटमा हुन्छ। ठेगाना <code class="text-brand-300">POST /api/sms/send</code> र <code class="text-brand-300">POST /api/sms/credit</code> हो। ग्राहकले SMS API मा टोकन बनाउँछ। टोकन एक पटक देखिन्छ, बढीमा ५ वटा सक्रिय रहन्छ, र एक मिनेटमा ३० पटकसम्म चल्छ।</p>
        <p>नम्बर ९७ वा ९८ बाट सुरु हुने १० अंकको नेपाली मोबाइल हुनुपर्छ। एक पटकमा बढीमा ५०० नम्बर। अक्षर १६० मा १ क्रेडिट, युनिकोड ७० मा १ क्रेडिट। कानुनले नमिल्ने ढाँचा रोकिन्छ र क्रेडिट काटिँदैन।</p>
        <p>तालिकामा राखिएको SMS क्रोनले पठाउँछ। सर्भरमा हरेक मिनेट <code class="text-brand-300">php cron/sms-queue.php</code> चलाउनुहोस्। वेबबाट खोल्दा <code class="text-brand-300">CRON_KEY</code> चाहिन्छ।</p>
    </div>
</section>

<section id="voice" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">११. आवाज कल</h2></div>
    <ol class="p-5 list-decimal pl-10 space-y-2 text-sm text-slate-300">
        <li>ग्राहकले सन्देश र नम्बर सेभ गर्छ। साइटले आफैँ डायल गर्दैन।</li>
        <li>Messages मा पर्खाइमा रहेको आवाज काम खोल्नुहोस्। आफ्नो आवाज लाइनबाट कल लगाउनुहोस्।</li>
        <li>Mark placed थिच्नुहोस्। त्यसबेला ग्राहकको आवाज क्रेडिट काटिन्छ र ग्राहकले Placed देख्छ।</li>
        <li>गलत काटिएको क्रेडिट Return credits बाट फर्काउन सकिन्छ। दोस्रो पटक फर्किँदैन।</li>
    </ol>
</section>

<section id="settings" class="dash-panel mb-6">
    <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">१२. सेटिङ र मेल</h2></div>
    <div class="p-5 space-y-2 text-sm text-slate-300">
        <p>Settings मा साइटको नाम, फोन, लोगो, भुक्तानी विवरण, र सूचना इमेल राख्नुहोस्। मेलको छोटो नाम <strong class="text-white">Aakash Tech</strong> हो। पठाउने ठेगाना अहिले <strong class="text-white">noreply@aakashtechnologies.com.np</strong> हो। अर्को मेलबक्स चाहिँए त्यही फिल्डमा लेखेर सेभ गर्नुहोस्।</p>
        <p>दर्ता, वालेट भुक्तानी, सेवा किन्दा, वेबसाइट वा तालिम बुक हुँदा, डोमेन, र नवीकरणमा ग्राहकलाई आफैँ मेल जान्छ। वेबसाइट तयार, तालिम मिति, होस्टिङ लगइन, मेलबक्स, परिचय, र सपोर्ट टिकटमा पनि मेल जान्छ। सार्वजनिक फारम भर्नेलाई पनि प्राप्ति मेल जान्छ। साइन इन पूरा हुँदा इमेलमा खाता नम्बर, इमेल, IP, र नेपाल समय जान्छ। SMS पठाइँदैन, त्यसैले थोक लाइन र ग्राहकको क्रेडिट दुवै काटिँदैन। आधा घण्टाभित्र उही ठेगानाबाट फेरि साइन इन हुँदा दोहोरिँदैन। कुन काममा कुन विषय र लेख जान्छ भन्ने ड्राफ्ट Settings को Mail the client receives मा छ। पासवर्ड मेलमा लेखिँदैन।</p>
        <p>cPanel मा From ठेगाना यही डोमेनको मेलबक्स हुनुपर्छ, नत्र मेल नजान सक्छ।</p>
        <p>गोपनीयता नीति र कुकी सूचना Settings को Privacy and cookies मा सम्पादन हुन्छ। खाली पाठले तयार पारिएको लेख फर्काउँछ। सार्वजनिक फुटरमा Privacy र Cookies लिंक छ। तयार लेख वैयक्तिक गोपनीयता ऐन, २०७५ र नियमावली, २०७७ अनुसार हो। साइटले साइन इनको लागि एउटा कुकी राख्छ। विज्ञापन कुकी राखिँदैन।</p>
        <p>नवीकरण क्रोन हरेक दिन <code class="text-brand-300">php cron/renewals.php</code> चलाउनुहोस्।</p>
        <p>डाटाबेस र प्रशासक खाता <code class="text-brand-300">cpanel-config.php</code> मा हुन्छ। त्यो फाइल वेबबाट खोलिँदैन।</p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
