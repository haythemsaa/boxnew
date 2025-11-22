import React, { useState } from 'react';
import {
  View,
  Text,
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

const ChangePasswordScreen = ({ navigation }) => {
  const { logout } = useAuth();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
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

    if (!formData.current_password || !formData.password || !formData.password_confirmation) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs');
      return;
    }

    if (formData.password !== formData.password_confirmation) {
      Alert.alert('Erreur', 'Les nouveaux mots de passe ne correspondent pas');
      return;
    }

    if (formData.password.length < 8) {
      Alert.alert('Erreur', 'Le mot de passe doit contenir au moins 8 caractères');
      return;
    }

    setLoading(true);

    try {
      await customerService.updatePassword(formData);

      Alert.alert(
        'Succès',
        'Mot de passe modifié avec succès. Vous allez être déconnecté.',
        [
          {
            text: 'OK',
            onPress: async () => {
              await logout();
            },
          },
        ]
      );
    } catch (error) {
      console.error('Error changing password:', error);
      setErrors(error.errors || {});
      Alert.alert('Erreur', error.message || 'Impossible de changer le mot de passe');
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
            <Text style={styles.info}>
              Après avoir changé votre mot de passe, vous serez déconnecté et devrez vous
              reconnecter avec votre nouveau mot de passe.
            </Text>
          </Card>

          <Card>
            <Input
              label="Mot de passe actuel *"
              value={formData.current_password}
              onChangeText={(value) => handleChange('current_password', value)}
              placeholder="••••••••"
              secureTextEntry
              autoCapitalize="none"
              error={errors.current_password?.[0]}
            />

            <Input
              label="Nouveau mot de passe *"
              value={formData.password}
              onChangeText={(value) => handleChange('password', value)}
              placeholder="••••••••"
              secureTextEntry
              autoCapitalize="none"
              error={errors.password?.[0]}
            />

            <Input
              label="Confirmer le nouveau mot de passe *"
              value={formData.password_confirmation}
              onChangeText={(value) => handleChange('password_confirmation', value)}
              placeholder="••••••••"
              secureTextEntry
              autoCapitalize="none"
            />

            <Text style={styles.hint}>
              Le mot de passe doit contenir au moins 8 caractères.
            </Text>
          </Card>

          <View style={styles.actions}>
            <Button
              title="Changer le mot de passe"
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
  info: {
    fontSize: 14,
    color: COLORS.text.secondary,
    lineHeight: 20,
  },
  hint: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginTop: -8,
  },
  actions: {
    marginBottom: 24,
  },
  cancelButton: {
    marginTop: 12,
  },
});

export default ChangePasswordScreen;
