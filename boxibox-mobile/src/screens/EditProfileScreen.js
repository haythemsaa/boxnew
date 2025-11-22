import React, { useState } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import customerService from '../services/customerService';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import { COLORS } from '../constants/colors';

const EditProfileScreen = ({ navigation }) => {
  const { user, updateUser } = useAuth();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    phone: user?.phone || '',
    phone_secondary: user?.phone_secondary || '',
    address: user?.address || '',
    postal_code: user?.postal_code || '',
    city: user?.city || '',
    country: user?.country || '',
  });
  const [errors, setErrors] = useState({});

  const handleChange = (field, value) => {
    setFormData({ ...formData, [field]: value });
    if (errors[field]) {
      setErrors({ ...errors, [field]: undefined });
    }
  };

  const handleSubmit = async () => {
    setErrors({});
    setLoading(true);

    try {
      const response = await customerService.updateProfile(formData);

      if (response.customer) {
        await updateUser(response.customer);
      }

      Alert.alert('Succès', 'Profil mis à jour avec succès', [
        {
          text: 'OK',
          onPress: () => navigation.goBack(),
        },
      ]);
    } catch (error) {
      console.error('Error updating profile:', error);
      setErrors(error.errors || {});
      Alert.alert('Erreur', error.message || 'Impossible de mettre à jour le profil');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          style={styles.scrollView}
          keyboardShouldPersistTaps="handled"
        >
          <Card>
            <Input
              label="Téléphone principal"
              value={formData.phone}
              onChangeText={(value) => handleChange('phone', value)}
              placeholder="06 12 34 56 78"
              keyboardType="phone-pad"
              error={errors.phone?.[0]}
            />

            <Input
              label="Téléphone secondaire"
              value={formData.phone_secondary}
              onChangeText={(value) => handleChange('phone_secondary', value)}
              placeholder="01 23 45 67 89"
              keyboardType="phone-pad"
              error={errors.phone_secondary?.[0]}
            />

            <Input
              label="Adresse"
              value={formData.address}
              onChangeText={(value) => handleChange('address', value)}
              placeholder="123 Rue de la Paix"
              error={errors.address?.[0]}
            />

            <Input
              label="Code postal"
              value={formData.postal_code}
              onChangeText={(value) => handleChange('postal_code', value)}
              placeholder="75001"
              keyboardType="numeric"
              error={errors.postal_code?.[0]}
            />

            <Input
              label="Ville"
              value={formData.city}
              onChangeText={(value) => handleChange('city', value)}
              placeholder="Paris"
              error={errors.city?.[0]}
            />

            <Input
              label="Pays"
              value={formData.country}
              onChangeText={(value) => handleChange('country', value)}
              placeholder="France"
              error={errors.country?.[0]}
            />
          </Card>

          <View style={styles.actions}>
            <Button
              title="Enregistrer"
              onPress={handleSubmit}
              loading={loading}
            />
            <Button
              title="Annuler"
              onPress={() => navigation.goBack()}
              variant="outline"
              style={styles.cancelButton}
            />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  keyboardView: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  actions: {
    marginBottom: 24,
  },
  cancelButton: {
    marginTop: 12,
  },
});

export default EditProfileScreen;
