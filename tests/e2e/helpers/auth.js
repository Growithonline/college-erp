/**
 * Login helper — full flow handle karta hai: credentials + OTP bypass
 *
 * Required env vars (tests chalane se pehle set karo):
 *   TEST_INSTITUTE_UID  — institute ka UID (jaise INST-ABC123)
 *   TEST_EMAIL          — admin email
 *   TEST_PASSWORD       — admin password
 *
 * Server pe PLAYWRIGHT_TESTING=true hona chahiye jab test chala rahe ho.
 */
export async function loginAsInstitute(page) {
    await page.goto('/login');

    await page.fill('input[name="institute_id"]', process.env.TEST_INSTITUTE_UID || '');
    await page.fill('input[name="email"]', process.env.TEST_EMAIL || '');
    await page.fill('input[name="password"]', process.env.TEST_PASSWORD || '');

    // Login button mein type attribute nahi hai, isliye class se click karo
    await page.click('.btn-primary');

    // OTP page aayega — test bypass code daalo
    await page.waitForURL('**/otp-verify**');
    await page.fill('input[name="otp"]', '999999');
    await page.click('button[type="submit"]');

    await page.waitForURL('**/dashboard**');
}
