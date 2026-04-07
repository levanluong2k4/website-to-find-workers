import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

import { expect, test } from '@playwright/test';

test.describe.configure({ mode: 'serial' });

function seedAdminCrmFixtures() {
    const output = execFileSync('php', ['artisan', 'playwright:seed-admin-crm', '--json'], {
        cwd: process.cwd(),
        encoding: 'utf8',
    });
    const jsonLine = output
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .at(-1);

    if (!jsonLine) {
        throw new Error('Missing fixture payload from playwright:seed-admin-crm');
    }

    return JSON.parse(jsonLine);
}

function isJsonResponse(response, pathname) {
    const url = new URL(response.url());

    return url.pathname === pathname && response.request().method() !== 'OPTIONS';
}

async function loginAsAdmin(page, admin) {
    await page.goto('/login?role=admin');
    await expect(page.locator('#loginForm')).toBeVisible();

    await page.locator('#email').fill(admin.email);
    await page.locator('#matKhau').fill(admin.password);

    const [loginResponse] = await Promise.all([
        page.waitForResponse((response) => isJsonResponse(response, '/api/login')),
        page.locator('#btnSubmit').click(),
    ]);
    const loginPayload = await loginResponse.json();
    const debugOtp = loginPayload?.debug_otp;

    expect(debugOtp).toMatch(/^\d{6}$/);

    await page.waitForURL(/\/otp\?/);
    await expect(page.locator('#otpForm')).toBeVisible();
    await page.locator('.otp-input').first().click();
    await page.keyboard.type(debugOtp);

    const [verifyResponse] = await Promise.all([
        page.waitForResponse((response) => isJsonResponse(response, '/api/verify-otp')),
        page.locator('#btnVerify').click(),
    ]);
    const verifyPayload = await verifyResponse.json();

    expect(verifyPayload?.access_token).toBeTruthy();
    expect(verifyPayload?.user?.role).toBe('admin');

    await page.waitForURL('**/admin/dashboard');
}

test.describe('admin customer management basic flow', () => {
    let fixtures;

    test.beforeAll(() => {
        fixtures = seedAdminCrmFixtures();
    });

    test('admin can manage customers with basic list, detail, booking history and complaint handling', async ({ page }) => {
        test.slow();

        const customerName = fixtures.customer.name;
        const internalNote = 'Da goi xac nhan va huong dan khach cho buoc xu ly tiep theo.';
        const resolutionNote = 'Admin da lien he lai va ghi nhan huong xu ly cho khach.';

        await loginAsAdmin(page, fixtures.admin);

        await test.step('filter the customer list and open the customer detail page', async () => {
            await page.goto('/admin/customers');
            await expect(page.locator('#customerTableBody')).toContainText(customerName);

            await Promise.all([
                page.waitForResponse((response) => {
                    if (!isJsonResponse(response, '/api/admin/customers')) {
                        return false;
                    }

                    const params = new URL(response.url()).searchParams;
                    return params.get('search') === customerName;
                }),
                page.locator('#customerSearchInput').fill(customerName),
            ]);

            await Promise.all([
                page.waitForResponse((response) => {
                    if (!isJsonResponse(response, '/api/admin/customers')) {
                        return false;
                    }

                    const params = new URL(response.url()).searchParams;
                    return params.get('search') === customerName && params.get('status') === 'has_booking';
                }),
                page.locator('#customerStatusFilter').selectOption('has_booking'),
            ]);

            const customerRow = page.locator(`[data-customer-id="${fixtures.customer.id}"]`);
            await expect(customerRow).toContainText(customerName);
            await expect(page.locator('#customerPreviewPanel')).toContainText(fixtures.customer.phone);

            await page.locator(`a[href="/admin/customers/${fixtures.customer.id}"]`).first().click();
            await page.waitForURL(`**/admin/customers/${fixtures.customer.id}`);
        });

        await test.step('add an internal note in customer detail', async () => {
            await expect(page.locator('#customer360Title')).toHaveText(customerName);

            const [noteResponse] = await Promise.all([
                page.waitForResponse((response) => {
                    const url = new URL(response.url());

                    return url.pathname === `/api/admin/customers/${fixtures.customer.id}/notes`
                        && response.request().method() === 'POST';
                }),
                (async () => {
                    await page.locator('#customer360NoteCategory').selectOption('cskh');
                    await page.locator('#customer360NoteContent').fill(internalNote);
                    await page.locator('#customer360NoteForm button[type="submit"]').click();
                })(),
            ]);

            const notePayload = await noteResponse.json();
            expect(notePayload?.status).toBe('success');
            await expect(page.locator('#customer360Notes')).toContainText(internalNote);
        });

        await test.step('review booking history of the customer', async () => {
            await page.locator(`a[href="/admin/customers/${fixtures.customer.id}/bookings"]`).click();
            await page.waitForURL(`**/admin/customers/${fixtures.customer.id}/bookings`);
            await expect(page.locator('#customerHistoryTitle')).toContainText(customerName);

            await Promise.all([
                page.waitForResponse((response) => {
                    if (!isJsonResponse(response, `/api/admin/customers/${fixtures.customer.id}/bookings`)) {
                        return false;
                    }

                    return new URL(response.url()).searchParams.get('status') === 'da_xong';
                }),
                page.locator('#customerHistoryStatus').selectOption('da_xong'),
            ]);

            await expect(page.locator('#customerHistoryTableBody')).toContainText(fixtures.booking.code);
            await expect(page.locator('#customerHistoryPreview')).toContainText(fixtures.service.name);

            await page.locator('#customerHistoryDetailLink').click();
            await page.waitForURL(`**/admin/customers/${fixtures.customer.id}`);
        });

        await test.step('claim and resolve the customer complaint', async () => {
            await page.goto(`/admin/customer-feedback?customer=${fixtures.customer.id}`);
            await expect(page.locator('#customerFeedbackList')).toContainText(customerName);

            await page.locator(`[data-case-id="${fixtures.feedback.case_key}"]`).click();
            await expect(page.locator('#customerFeedbackPreview')).toContainText(customerName);

            const claimResponsePromise = page.waitForResponse((response) => {
                const url = new URL(response.url());

                return url.pathname === `/api/admin/customer-feedback/${fixtures.feedback.case_key}/claim`
                    && response.request().method() === 'POST';
            });

            await page.locator('[data-feedback-action="claim"]').click();
            await expect(page.locator('.swal2-confirm')).toBeVisible();
            await page.locator('.swal2-confirm').click();

            const claimResponse = await claimResponsePromise;
            const claimPayload = await claimResponse.json();

            expect(claimPayload?.status).toBe('success');
            await expect(page.locator('#customerFeedbackPreview')).toContainText(fixtures.admin.name);

            const resolveResponsePromise = page.waitForResponse((response) => {
                const url = new URL(response.url());

                return url.pathname === `/api/admin/customer-feedback/${fixtures.feedback.case_key}/resolve`
                    && response.request().method() === 'POST';
            });

            await page.locator('[data-feedback-action="resolve"]').click();
            await expect(page.locator('.swal2-textarea')).toBeVisible();
            await page.locator('.swal2-textarea').fill(resolutionNote);
            await page.locator('.swal2-confirm').click();

            const resolveResponse = await resolveResponsePromise;
            const resolvePayload = await resolveResponse.json();

            expect(resolvePayload?.status).toBe('success');
            await expect(page.locator('#customerFeedbackPreview')).toContainText('Da xu ly');
            await expect(page.locator('#customerFeedbackPreview')).toContainText(resolutionNote);
        });

        await test.step('persist a lightweight evidence artifact for the last page state', async () => {
            const screenshotPath = path.join(os.tmpdir(), 'playwright-admin-customer-basic.png');
            await page.screenshot({ path: screenshotPath, fullPage: true });

            const screenshotStat = await fs.stat(screenshotPath);
            expect(screenshotStat.size).toBeGreaterThan(0);
        });
    });
});
