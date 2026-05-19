<template>
    <ICard>
        <ICardHeader>
            <ICardHeading>Connect Google Account</ICardHeading>
        </ICardHeader>

        <ICardBody>
            <IButton :loading="loading" variant="primary" @click="connectToGoogle">
                Connect with Google
            </IButton>
        </ICardBody>
    </ICard>
</template>

<script setup>
import { ref } from 'vue'

const loading = ref(false)

const connectToGoogle = async () => {
    loading.value = true
    try {
        const response = await Innoclapps.request().get('/google/auth');
        window.location.href = response.data.url
    } catch (e) {
        console.error('Failed to get auth URL', e)
    } finally {
        loading.value = false
    }
}
</script>
