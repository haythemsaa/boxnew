import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import Button from '../components/Button';
import Input from '../components/Input';
import { COLORS } from '../constants/colors';

const RegisterScreen = ({ navigation }) => {
  const { register } = useAuth();
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleChange = (field, value) => {
    setFormData({ ...formData, [field]: value });
    // Clear error for this field
    if (errors[field]) {
      setErrors({ ...errors, [field]: undefined });
    }
  };

  const handleRegister = async () => {
    // Reset errors
    setErrors({});

    // Validate
    if (!formData.name || !formData.email || !formData.password) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs requis');
      return;
    }

    if (formData.password !== formData.password_confirmation) {
      Alert.alert('Erreur', 'Les mots de passe ne correspondent pas');
      return;
    }

    setLoading(true);

    try {
      const result = await register(formData);

      if (!result.success) {
        setErrors(result.errors || {});
        Alert.alert('Erreur', result.message || 'Échec de l\'inscription');
      }
    } catch (error) {
      Alert.alert('Erreur', 'Une erreur est survenue');
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
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
        >
          <View style={styles.header}>
            <Text style={styles.title}>Créer un compte</Text>
            <Text style={styles.subtitle}>Rejoignez Boxibox</Text>
          </View>

          <View style={styles.form}>
            <Input
              label="Nom complet *"
              value={formData.name}
              onChangeText={(value) => handleChange('name', value)}
              placeholder="Jean Dupont"
              error={errors.name?.[0]}
            />

            <Input
              label="Email *"
              value={formData.email}
              onChangeText={(value) => handleChange('email', value)}
              placeholder="exemple@email.com"
              keyboardType="email-address"
              autoCapitalize="none"
              error={errors.email?.[0]}
            />

            <Input
              label="Téléphone"
              value={formData.phone}
              onChangeText={(value) => handleChange('phone', value)}
              placeholder="06 12 34 56 78"
              keyboardType="phone-pad"
              error={errors.phone?.[0]}
            />

            <Input
              label="Mot de passe *"
              value={formData.password}
              onChangeText={(value) => handleChange('password', value)}
              placeholder="••••••••"
              secureTextEntry
              autoCapitalize="none"
              error={errors.password?.[0]}
            />

            <Input
              label="Confirmer le mot de passe *"
              value={formData.password_confirmation}
              onChangeText={(value) => handleChange('password_confirmation', value)}
              placeholder="••••••••"
              secureTextEntry
              autoCapitalize="none"
            />

            <Button
              title="S'inscrire"
              onPress={handleRegister}
              loading={loading}
              style={styles.registerButton}
            />

            <Button
              title="Retour à la connexion"
              onPress={() => navigation.goBack()}
              variant="outline"
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
  scrollContent: {
    flexGrow: 1,
    padding: 24,
  },
  header: {
    alignItems: 'center',
    marginTop: 24,
    marginBottom: 32,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: COLORS.text.secondary,
  },
  form: {
    width: '100%',
  },
  registerButton: {
    marginBottom: 12,
  },
});

export default RegisterScreen;
