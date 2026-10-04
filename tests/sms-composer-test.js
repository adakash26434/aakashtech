// Run: node tests/sms-composer-test.js   (checks the composer's counting without a browser)
const fs = require('fs');
const vm = require('vm');
const code = fs.readFileSync(__dirname + '/../assets/js/sms-portal.js', 'utf8');
const sandbox = { console, Array, String, Math, document: { getElementById: () => null, querySelector: () => null } };
vm.createContext(sandbox);
vm.runInContext(code + '\nthis.smsComposer = smsComposer; this.smsNepalDigits = smsNepalDigits;', sandbox);
let failed = 0;
function check(name, ok) { console.log((ok ? 'PASS ' : 'FAIL ') + name); if (!ok) failed++; }
function make(seed) { return sandbox.smsComposer(Object.assign({ text: '', numbers: '', balance: 1000 }, seed)); }

let c = make({ text: 'Hello', numbers: '9841000001\n9841000001\n+977 9841000002\n12345\n9801000003' });
let e = c.measure();
check('counts valid numbers once each', e.count === 3);
check('counts repeated numbers', e.dupes === 1);
check('counts pieces that are not Nepal mobiles (12345); +977 with a space is now joined', e.bad === 1);
check('one credit per number for a short English text', e.credits === 3);

c = make({ text: 'a'.repeat(161), numbers: '9841000001' });
e = c.measure();
check('161 English characters is 2 parts', e.parts === 2 && e.meter.part === 2 && e.meter.capacity === 153);
check('meter says how many characters are left', e.meter.left === 2 * 153 - 161);

c = make({ text: 'नमस्ते', numbers: '9841000001' });
e = c.measure();
check('Nepali text is detected with 70-character parts', e.meter.language === 'Nepali' && e.meter.capacity === 70);

c = make({ text: 'Hi', numbers: '9841000001\nabc\n12345\n9841000001\nRam 9841000002' });
c.removeInvalid();
check('clean-up keeps valid numbers once and drops the rest', c.numbers === '9841000001\nRam 9841000002');
check('after clean-up nothing is invalid or repeated', make({ text: 'Hi', numbers: c.numbers }).measure().bad === 0 && make({ text: 'Hi', numbers: c.numbers }).measure().dupes === 0);

c = make({ text: 'Hi', numbers: '9841000001', balance: 0 });
check('not enough credits is flagged', c.measure().short === true);

c = make({ text: 'Hi', numbers: '+977 9841000002\n984 100 0004\nRam Thapa 98410 00005\n9841000001 9841000009' });
e = c.measure();
check('numbers typed with spaces are joined (4 lines, 5 numbers, none invalid)', e.count === 5 && e.bad === 0);
check('two numbers on one line stay two numbers', make({ text: 'Hi', numbers: '9841000001 9841000009' }).measure().count === 2);
check('a name before a spaced number is kept as a name', make({ text: '{name} hi', numbers: 'Ram Thapa 984 100 0008' }).measure().preview === 'Ram Thapa hi');
process.exit(failed ? 1 : 0);
