// Self-hosted fonts (SIL Open Font License; notices ship in node_modules/@fontsource/*/LICENSE).
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
import '@fontsource/manrope/latin-500.css';
import '@fontsource/manrope/latin-600.css';
import '@fontsource/manrope/latin-700.css';
import '@fontsource/manrope/latin-800.css';

import { initNavigation } from './modules/navigation';
import { initForms } from './modules/forms';
import { initRepeaters } from './modules/repeaters';

document.documentElement.classList.add('js');

initNavigation();
initForms();
initRepeaters();
