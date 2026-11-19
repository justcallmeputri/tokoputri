const axios = require('axios');
const crypto = require('crypto');

const merchantCode = 'YOUR_SANDBOX_MERCHANT_CODE';
const apiKey = 'YOUR_SANDBOX_API_KEY';
const merchantOrderId = 'ORDER-12345'; // Masukkan merchantOrderId yang ingin dicek

// Buat signature untuk cek status (Format Duitku: md5(merchantCode + merchantOrderId + apiKey))
const rawSignature = merchantCode + merchantOrderId + apiKey;
const signature = crypto.createHash('md5').update(rawSignature).digest('hex');

async function checkTransactionStatus() {
    try {
        const response = await axios.post('https://api-sandbox.duitku.com/webapi/api/merchant/transactionStatus', {
            merchantCode: merchantCode,
            merchantOrderId: merchantOrderId,
            signature: signature
        });

        console.log('Status Transaksi:', response.data);
        // Jika sudah dibayar, statusCode biasanya "00"
    } catch (error) {
        console.error('Gagal mengecek status:', error.response?.data || error.message);
    }
}

checkTransactionStatus();